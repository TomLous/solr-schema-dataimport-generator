<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     16/06/15 12:02
 */

function jsonToXML(&$doc, &$el, $data, $dependancyData){
    foreach($data as $key=>$val){
        $key = dependencyParser($key, $dependancyData);


        if(is_scalar($val)){
            if($val === true){
                $val = 'true';
            }elseif($val === false){
                $val = 'false';
            }


            if($key != 'pattern'){
                $val = dependencyParser($val, $dependancyData);
            }

            if($val !== null){
                $el->setAttribute($key, $val);
            }
        }elseif(is_array($val)){
            foreach($val as $subval){
                $subel = $doc->createElement($key);
                jsonToXML($doc, $subel, $subval, $dependancyData);
                $el->appendChild($subel);
            }
        }else{
            $subel = $doc->createElement($key);
            jsonToXML($doc, $subel, $val, $dependancyData);
            $el->appendChild($subel);
        }

    }
}


function arrayCartesian($arrays) {
    $result = array();
    $keys = array_keys($arrays);
    $reverse_keys = array_reverse($keys);
    $size = intval(count($arrays) > 0);
    foreach ($arrays as $array) {
        $size *= count($array);
    }
    for ($i = 0; $i < $size; $i ++) {
        $result[$i] = array();
        foreach ($keys as $j) {
            $result[$i][$j] = current($arrays[$j]);
        }
        foreach ($reverse_keys as $j) {
            if (next($arrays[$j])) {
                break;
            }
            elseif (isset ($arrays[$j])) {
                reset($arrays[$j]);
            }
        }
    }
    return $result;
}

function createDependencyArray(&$fieldInfo, &$dependencyVariables){
    $dependancyArray = array(array());
    if(isset($fieldInfo['dependency'])){
        $combinedArrays = array();
        foreach($fieldInfo['dependency'] as $dependancyPlaceholder=>$dependancyVariable){
            if($dependencyVariables[$dependancyVariable]){
                $combinedArrays[$dependancyPlaceholder] = $dependencyVariables[$dependancyVariable];
            }
        }
        $dependancyArray = arrayCartesian($combinedArrays);
    }
    return $dependancyArray;
}

function dependencyParser($val, &$data){
    return preg_replace('/\[([^\]]+)\]/ies', "\$data['\\1'];", $val);
}