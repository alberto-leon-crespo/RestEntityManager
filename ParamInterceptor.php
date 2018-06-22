<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 22/06/18
 * Time: 4:52
 */

namespace ALC\RestEntityManager;


abstract class ParamInterceptor
{
    protected $arrFieldsMap;
    protected $arrFieldsType;
    protected $arrFieldsValues;

    protected function __construct($arrFieldsMap, $arrFieldsType, $arrFieldsValues)
    {
        $this->arrFieldsMap = $arrFieldsMap;
        $this->arrFieldsType = $arrFieldsType;
        $this->arrFieldsValues = $arrFieldsValues;
    }

    protected function readClassAnnotation(){

    }

    private function matchEntityFieldsWithResourcesFieldsRecursive( $array )
    {
        foreach ($array as $propertyName => $value) {

            $path = explode(".", $propertyName);

            $field = array_shift($path);

            if (strpos($propertyName, ".") !== false) {

                if (!empty($field)) {

                    if (array_key_exists($field, $this->fieldsMap)) {

                        if (class_exists($this->fieldsType[$field])) {

                            $this->entityFinalFilterPath .= "." . $this->fieldsMap[$field];

                            $this->readClassAnnotations($this->fieldsType[$field]);

                            $array = array(
                                implode(".", $path) => $value
                            );

                            $this->matchEntityFieldsWithResourcesFieldsRecursive($array);

                        } else {

                            if (array_key_exists($field, $this->fieldsMap)) {

                                $this->entityFinalFilterPath .= "." . $this->fieldsMap[$field];

                                $this->arrayMatchedParams[$this->entityFinalFilterPath] = $value;

                            }

                            $this->readClassAnnotations($this->fieldsType[$field]);

                            $array = array(
                                implode(".", $path) => $value
                            );

                            $this->matchEntityFieldsWithResourcesFieldsRecursive($array);

                        }

                    }

                }

            } else {

                if (array_key_exists($field, $this->fieldsMap)) {

                    $this->entityFinalFilterPath .= "." . $this->fieldsMap[$field];

                    $this->entityFinalFilterPath = substr($this->entityFinalFilterPath, 1);

                    $this->arrayMatchedParams[$this->entityFinalFilterPath] = $value;

                }

            }

        }

    }
}