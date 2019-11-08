<?php

namespace Dynamo\ORM;

abstract class ActiveModel implements HydratableInterface
{
    private $hydrating = false;
    private $_cachedGetters = [];
    private $_changes; //Pending changes to persist
    private static $_persistableFieldMap;

    public static function getTableName()
    {
        return strtolower(
            preg_replace(
                '/(?<!^)[A-Z]/', '_$0',
                (new \ReflectionClass(static::class))->getShortName()
            )
        );
    }

    public static function getEntityClass($rawdata)
    {
        return static::class;
    }

    public static function beforeFind(ActiveQuery $query)
    {
        return $query;
    }

    public function getPrimaryKeyValue()
    {
        return $this->id;
    }

    public static function find($conditions = null)
    {
        $tableName = static::getTableName();

        return (new ActiveQuery(static::class))
            ->where(
                is_numeric($conditions) ?
                [ 'id' => (int) $conditions ] :
                $conditions
            );
    }

    public static function findOne($conditions)
    {
        return self::find($conditions)->fetchOne();
    }

    public function hydrate($data)
    {
        $hydratable = $this->getHydratableFields();
        $ignore = $this->getIgnoreFields();
        $this->hydrating = true;

        foreach($data as $key => $value){
            if(
                ($hydratable == null || count($hydratable) == 0 || in_array($key, $hydratable)) &&
                ($ignore == null || count($ignore) == 0 || !in_array($key, $ignore))
            ){
                $this->$key = $value;
            }
        }
        $this->hydrating = false;
        $this->afterHydrate();
    }

    public function getHydratableFields()
    {
        return null;
    }

    public function getIgnoreFields()
    {
        return null;
    }

    protected function afterHydrate()
    {
        
    }

    /**
     * Repopulates the model from the database
     */
    public function refresh()
    {
        $this->hydrate(
            (new Query)->from(static::getTableName())
            ->where(['id' => $this->id])
            ->fetchOne()
        );

        return $this;
    }

    /**
     * @param string $relatedModelClass
     */
    public function hasOne($relatedModelClass, $foreignKey = null, $localKey = 'id')
    {
        return new RelationQuery(
            static::class,
            $relatedModelClass,
            false,
            true,
            $foreignKey,
            $localKey
        );
    }

    public function belongsTo($relatedModelClass, $foreignKey = null, $localKey = 'id')
    {
        return new RelationQuery(
            static::class,
            $relatedModelClass,
            false,
            false,
            $foreignKey,
            $localKey
        );
    }

    /**
     * @param string $relatedClass
     * @param string $foreignKey Name of the foreign key field (in the related model table)
     * that points to this model.
     */
    public function hasMany($relatedClass, $foreignKey = null, $localKey = 'id')
    {
        return new RelationQuery(
            static::class,
            $relatedClass,
            true,
            true,
            $foreignKey,
            $localKey
        );
    }

    /*protected function pickForeignKeyField($class = null)
    {
        return strtolower(
            preg_replace(
                '/(?<!^)[A-Z]/', '_$0',
                basename(
                    $class !== null ?
                    $class :
                    static::class
                )
            )
        ).'_id';
    }*/

    public function __get($fieldName)
    {
        if($fieldName == ''){
            throw new \Exception('Trying to get empty field!!!');
        }
        if( property_exists($this, $fieldName) || method_exists($this, $fieldName) ){
            return $this->$fieldName;
        } else if(method_exists($this, $getter = "get".ucfirst($fieldName))){
            if(!isset($this->_cachedGetters[$fieldName])){
                $value = $this->$getter();
                if($value instanceof RelationQuery){
                    $relation = $value;
                    $localField = $relation->getKeyFieldOnParentTable();
                    if(($dot = strpos($localField,'.')) !== false){
                        $localField = \substr($localField, $dot + 1);
                    }
                    $value = $relation->where([
                        $relation->getKeyFieldOnChildTable() => $this->$localField
                    ])->fetch();
                }
                $this->_cachedGetters[$fieldName] = $value;
            }

            return $this->_cachedGetters[$fieldName];
        } else {
            throw new \Exception("Field '$fieldName' doesn't exists!");
        }
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
        if(!$this->hydrating){
            if(($mutator = $this->isPersistable($name))){
                $this->_changes[$name] = (
                    is_callable($mutator) ?
                    $mutator($value) :
                    $value
                );
                //echo "Changes:\n";var_dump($this->_changes);
            }
        }
    }

    public function save()
    {
        if(isset($this->_changes)){
            if(!isset($this->id)){
                $query = (new Query)->upsert(
                    static::getTableName(),
                    $this->_changes
                );
                if($query->execute()){
                    $this->id = (int) $query->getPdo()->lastInsertId();
                    $this->_changes = [];
                    return true;
                }
            } else {
                $query = (new Query)->update(
                    static::getTableName(),
                    $this->_changes,
                    [ 'id' => $this->id ]
                );
                if($query->execute()){
                    //var_dump($this->id);
                    $this->_changes = [];
                    return true;
                }
            }
        }

        return false;
    }

    public function update(array $fields)
    {
        return (new Query)->update(
            static::getTableName(),
            $fields,
            [ 'id' => $this->id ]
        )->execute();
    }

    /**
     * @return bool|callable If the field has a mutator, it will be returned
     */
    protected function isPersistable($fieldName)
    {
        $class = \get_class($this);

        if(!isset(self::$_persistableFieldMap[$class])){
            self::$_persistableFieldMap[$class] = self::getPersistableFields($class);
        }

        if( in_array($fieldName, self::$_persistableFieldMap[$class]) ){
            return true;
        } else if(isset(self::$_persistableFieldMap[$class][$fieldName])){
            if(is_callable(self::$_persistableFieldMap[$class][$fieldName])){
                return self::$_persistableFieldMap[$class][$fieldName];
            } else {
                return true;
            }
        }

        return false;
    }

    protected static function getPersistableFields($class)
    {
        $rows = (new Query)->select(['COLUMN_NAME','DATA_TYPE'])
            ->from('information_schema.COLUMNS')
            ->where([
                'TABLE_SCHEMA = DATABASE()',
                'TABLE_NAME' => $class::getTableName()
            ])
            ->fetchAll();

        $result = [];
        foreach($rows as $row){
            if($row['DATA_TYPE'] == 'datetime'){
                $result[$row['COLUMN_NAME']] = function($value){
                    if($value instanceof \DateTime){
                        return $value->format('Y-m-d H:i:s');
                    }
                    return $value;
                };
            } else {
                $result[] = $row['COLUMN_NAME'];
            }
        }

        return $result;
    }
}