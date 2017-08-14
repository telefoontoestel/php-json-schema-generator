<?php

namespace JSONSchema\Parsers;

use JSONSchema\Structure\Definition;
use JSONSchema\Mappers\StringMapper;

/**
 * Class JSONStringParser
 * @package JSONSchema\Parsers
 */
class JSONStringParser extends Parser
{

    /**
     *
     * @var array $itemFields
     */
    protected $itemFields = array();

    /**
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        parent::__construct(null, $config); // TODO: Change the autogenerated stub
    }


    /**
     * @param null|string $subject
     * @return $this
     */
    public function parse($subject = null)
    {
        // it could have been loaded elsewhere 
        if (!$subject) {
            $subject = $this->subject;
        }

        if (!$jsonObj = json_decode($subject)) {
            throw new Exceptions\UnprocessableSubjectException(
                "The JSONString subject was not processable - decode failed "
            );
        }

        $this->loadObjectProperties($jsonObj);
        $this->loadSchema();

        return $this;
    }

    /**
     * top level
     * every recurse under this will add to the properties of the property
     *
     * @param array $jsonObj
     */
    protected function loadObjectProperties($jsonObj)
    {
        // start walking the object 
        foreach ($jsonObj as $key => $property) {
            $this->appendProperty($key, $this->determineProperty($property));
        }
    }


    /**
     * due to the fact that determining property will be so different between
     * parser types we should probably just define this function here
     * In a JSON string it will be very simple.
     *   enter a string
     *   see what the string looks like
     *     check the maps of types
     *     see if it fits some semantics
     *
     * @param mixed $property
     * @return Definition
     */
    protected function determineProperty($property, $id = null)
    {
        $baseUrl = $this->configKeyExists('baseUrl') ? $this->getConfigSetting('baseUrl') : null;
        $requiredDefault = $this->configKeyExists('requiredDefault') ? $this->getConfigSetting(
            'requiredDefault'
        ) : false;

        $type = StringMapper::map($property);

        $prop = new Definition();
        $prop->setType($type)
             ->setRequired($requiredDefault);

        /*
            since this is an object get the properties of the sub objects
         */
        if (   $type == StringMapper::ARRAY_TYPE
            || $type == StringMapper::OBJECT_TYPE
        ) {

            $prop->setId($id);

            foreach ($property as $key => $p) {
                $def = $this->determineProperty($p, $prop->getId() ? $prop->getId().'/'.$key : null);
                ($type == StringMapper::OBJECT_TYPE) ? $prop->setProperty($key, $def) : $prop->addItem($def);
            }
        }

        return $prop;
    }


    /**
     * @param string $name
     * @param mixed  $item
     */
    protected function stackItemFields($name, $item)
    {
        // for non-loopables 
        if (!is_array($item) && !is_object($item)) {
            return;
        }
        foreach ($item as $key => $val) {
            $this->itemFields[$name][$key] = $val;
        }
    }


}
