<?php

namespace Dynamo\ORM;


class ActiveQuery extends Query
{
    protected $returnsMany = true;
    protected $with = [];
    protected $withQueries;
    protected $baseEntity;

    /**
     * @param string $class The fully namespaced name of a class that extends ActiveModel
     */
    public function __construct($class, ?\PDO $pdo = null)
    {
        parent::__construct($pdo);
        $this->baseEntity = $class;
        $tableName = $class::getTableName();
        $this->select("$tableName.*", true)
            ->from($tableName)
            ->into(function($rawdata) use ($class){
                return $class::getEntityClass($rawdata);
            });
    }

    /**
     * @param string|array $relations Relations to eager-load
     */
    public function with($relations)
    {
        if($relations !== null){
            $this->with = array_merge(
                $this->with,
                ( is_string($relations) ? [ $relations ] : $relations )
            );
        }

        return $this;
    }

    public function fetch()
    {
        if($this->returnsMany){
            return parent::fetchAll();
        } else {
            return parent::fetchOne();
        }
    }

    public function one()
    {
        return $this->fetchOne();
    }

    public function all()
    {
        return $this->fetchAll();
    }

    /**
     * Called by Query right after one single model has been hydrated
     */
    protected function afterModelHydrated($model)
    {
        if($this->with !== null){
            foreach($this->with as $key => $value){
                
                if(is_numeric($key)){
                    $relationName = $value;
                    $relationModifiers = null;
                    //$callable = null;
                } else if(is_string($key)){
                    $relationName = $key;
                    $relationModifiers = $value;
                    /*if(is_callable($value)){
                        $callable = $value;
                    } else if(is_array($value)){
                        $pendingRelations = $value;
                    }*/
                } else {
                    throw new \Exception("Unexpected relation definition");
                }
                    
                if( ($dot = strpos($relationName, '.')) !== false ){
                    $relationName = substr($relationName, 0, $dot);
                    $pendingRelations = \substr($value, $dot + 1);
                    if($relationModifiers !== null){
                        //pass the modifiers ahead
                        $pendingRelations = [ $pendingRelations => $relationModifiers ];
                        $relationModifiers = null;
                        //$callable = null;
                    }
                } else if($relationModifiers !== null && is_array($relationModifiers)) {
                    $pendingRelations = $relationModifiers;
                    $relationModifiers = null;
                } else {
                    $pendingRelations = null;
                }

                if(!isset($this->withQueries[$relationName])){
                    $getter = 'get'.ucfirst($relationName);
                    $this->withQueries[$relationName] = (object) [
                        'query' => $model->$getter()->with($pendingRelations),
                        //'callable' => $callable,
                        'modifiers' => $relationModifiers,
                        'ids' => new \Ds\Set(),
                        'fetched' => false,
                    ];
                }

                $relation = $this->withQueries[$relationName];
                $idField = $relation->query->getKeyFieldOnParentTable();
                if(($dot = strpos($idField, '.')) !== false ){
                    $idField = substr($idField, $dot + 1);
                }
                //echo "Agregando a listado (campo clave: $idField): {$model->$idField}\n";
                $relation->ids->add($model->$idField);
            }
        }
    }

    /**
     * Called by Query after all fetched models have been fetched and hydrated
     */
    protected function afterAllFetchedAndHydrated($result)
    {
        if($this->withQueries != null){
            foreach($this->withQueries as $relationName => $relationParams){
                if(!$relationParams->fetched){

                    /*if($relationParams->query == null){
                        echo "relationParams->query ES NULL para relacion '$relationName'!!!\n";
                        continue;
                    }*/

                    $keyFieldOnChildModel = $relationParams->query->getKeyFieldOnChildTable();
                    $keyFieldOnParentModel = $relationParams->query->getKeyFieldOnParentTable();

                    $query = ($relationParams->query->baseEntity)::beforeFind($relationParams->query)
                        ->where([
                            $keyFieldOnChildModel => $relationParams->ids->toArray()
                        ]);

                    if(isset($relationParams->modifiers)){
                        if(is_callable($relationParams->modifiers)){
                            $query = \call_user_func($relationParams->modifiers, $query);
                        } else if(is_array($relationParams->modifiers)){
                            $query = $query->with($relationModifiers);
                        }
                    }

                    $children = $query->fetchAll();

                    //Init relation field on parent objects
                    foreach($result as $parent){
                        if($relationParams->query->returnsMany){
                            $parent->$relationName = new \Ds\Vector();
                        } else {
                            $parent->$relationName = null;
                        }
                    }

                    $keyFieldOnChildren = $relationParams->query->getKeyFieldOnChildrenObjects();

                    if(($dot = strpos($keyFieldOnParentModel, '.')) !== false){
                        $keyFieldOnParentModel = \substr($keyFieldOnParentModel, $dot + 1);
                    }

                    foreach($children as $child){
                        //Assign child to its respective parent
                        foreach($result as $parent){
                            if($parent->$keyFieldOnParentModel == $child->$keyFieldOnChildren){
                                if ($relationParams->query->returnsMany){
                                    $parent->$relationName->push($child);
                                } else if ($parent->$relationName === null){
                                    $parent->$relationName = $child;
                                }
                                //break -- dont break! Big bug! hasMany relations may have duplicate children;
                            }
                        }
                    }

                    $relationParams->fetched = true;
                }

            }
        }
    }

    protected function guessTableName($class = null)
    {
        return strtolower(
            preg_replace(
                '/(?<!^)[A-Z]/', '_$0',
                (new \ReflectionClass($class !== null ? $class : $this->baseEntity))->getShortName()
            )
        );
    }
}