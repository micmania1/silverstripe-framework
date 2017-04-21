<?php

namespace SilverStripe\ORM;

/**
 * Representation of a DataModel - a collection of DataLists for each different
 * data type.
 *
 * Usage:
 * <code>
 * $model = new DataModel;
 * $mainMenu = $model->SiteTree->where('"ParentID" = 0 AND "ShowInMenus" = 1');
 * </code>
 */
class DataModel
{

    /**
     * @var DataModel
     */
    protected static $inst;

    /**
     * @var array $customDataLists
     */
    protected $customDataLists = array();

    /**
     * Get the global DataModel.
     *
     * @return DataModel
     */
    public static function inst()
    {
        if (!self::$inst) {
            self::$inst = new self;
        }

        return self::$inst;
    }

    /**
     * Set the global DataModel, used when data is requested from static
     * methods.
     *
     * @param DataModel $inst
     */
    public static function set_inst(DataModel $inst)
    {
        self::$inst = $inst;
    }

    /**
     * @param string
     *
     * @return DataList
     */
    public function __get($class)
    {
        if (isset($this->customDataLists[$class])) {
            return clone $this->customDataLists[$class];
        } else {
            $list = DataList::create($class);
            $list->setDataModel($this);

            return $list;
        }
    }

    /**
     * @param string $class
     * @param DataList $item
     */
    public function __set($class, $item)
    {
        $item = clone $item;
        $item->setDataModel($this);
        $this->customDataLists[$class] = $item;
    }
}
