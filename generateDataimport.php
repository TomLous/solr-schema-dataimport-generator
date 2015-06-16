<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     16/06/15 11:25
 */

print PHP_EOL."------------------------------------".PHP_EOL."Generating dataimport.xml".PHP_EOL;
include_once('utils.php');
// Make sure to escape all slashes in Json eg"pattern": "([\.,;:-_])", => "pattern": "([\\.,;:-_])",
$config = json_decode(file_get_contents("config/config.json"), true);

if(json_last_error()){
    "json decode error: " . json_last_error_msg();
    exit();
}

$dataimportDoc = new DOMDocument("1.0","UTF-8");
$dataimportDoc->preserveWhiteSpace = true;
$dataimportDoc->formatOutput = true;
$dataConfig = $dataimportDoc->createElement('dataConfig');

foreach($config['data_sources'] as $name=>$data){
    $dataSource = $dataimportDoc->createElement('dataSource');
    $dataSource->setAttribute('name', $name);
    foreach($data as $key=>$val){
        $dataSource->setAttribute($key, $val);
    }
    $dataConfig->appendChild($dataSource);
}


$fields = $config['fields'];
//unset($config['fields']);



// parsing XML creating readable dataimport.xml
$dataimportDoc->appendChild($dataConfig);

$xml = $dataimportDoc->saveXML();
$xml = preg_replace("/\s+<!--NEWLINE-->/is","\n",$xml);
print $xml;

file_put_contents('target/dataimport.xml', $xml);









// methods
//function createFieldConfig(&$doc, &$el, $fieldName,  $fieldInfo, $dependancyArray){
//    global $foundTypes;
//    foreach($dependancyArray as $pos=>$dependancyData){
//        $sourceFieldName = $fieldName;
//
//
//        foreach($fieldInfo['types'] as $fieldNamePostfix=>$fieldType){
//            $isSourceField = false;
//            $currentFieldName = $fieldName;
//            if($fieldNamePostfix == '_'){
//                $isSourceField = true;
//            }else{
//                $currentFieldName .= '_'.$fieldNamePostfix;
//            }
//
//            $currentFieldName = preg_replace('/\[([^\]]+)\]/ies', "\$dependancyData['\\1'];", $currentFieldName);
//            $currentFieldType = preg_replace('/\[([^\]]+)\]/ies', "\$dependancyData['\\1'];", $fieldType);
//
//            $foundTypes[$currentFieldType] = $currentFieldType;
//            if($isSourceField){
//                $sourceFieldName = $currentFieldName;
//                $comment = new DOMComment(" $currentFieldName ");
//                $el->appendChild($comment);
//            }
//
//            $indexed = isset($fieldInfo['indexed'])?$fieldInfo['indexed']:true;
//            $stored = isset($fieldInfo['stored'])&& $isSourceField?$fieldInfo['stored']:false;
//            $required = isset($fieldInfo['required'])&& $isSourceField?$fieldInfo['required']:false;
//            $multiValued = isset($fieldInfo['multiValued'])?$fieldInfo['multiValued']:null;
//
//
//            $field = $doc->createElement('field');
//            $field->setAttribute("name", $currentFieldName);
//            $field->setAttribute('type', $currentFieldType);
//            $field->setAttribute('indexed', $indexed?'true':'false');
//            $field->setAttribute('stored', $stored?'true':'false');
//            $field->setAttribute('required', $required?'true':'false');
//            if($multiValued !== null){
//                $field->setAttribute('multiValued', $multiValued?'true':'false');
//            }
//            $el->appendChild($field);
//            if(!$isSourceField){
//                $field = $doc->createElement('copyField');
//                $field->setAttribute('source', $sourceFieldName);
//                $field->setAttribute('dest', $currentFieldName);
//                $el->appendChild($field);
//
//            }
//
//        }
//        $comment = new DOMComment("NEWLINE");
//        $el->appendChild($comment);
//
//        if(isset($fieldInfo['uniqueKey'])){
//            $field = $doc->createElement('uniqueKey', $sourceFieldName);
//            $el->appendChild($field);
//            $comment = new DOMComment("NEWLINE");
//            $el->appendChild($comment);
//        }
//        if(isset($fieldInfo['defaultSearchField'])){
//            $field = $doc->createElement('defaultSearchField', $sourceFieldName);
//            $el->appendChild($field);
//            $comment = new DOMComment("NEWLINE");
//            $el->appendChild($comment);
//        }
//    }
//}
//
//function createFieldTypeConfig(&$doc, &$el, $fieldType,  $fieldTypeInfo, $dependancyArray){
//    global $foundTypes;
//
//    foreach($dependancyArray as $pos=>$dependancyData){
//        $currentFieldType = preg_replace('/\[([^\]]+)\]/ies', "\$dependancyData['\\1'];", $fieldType);
//
//        $comment = new DOMComment(" $currentFieldType ");
//        $el->appendChild($comment);
//
//        $field = $doc->createElement('fieldType');
//
//        jsonToXML($doc, $field, $fieldTypeInfo, $dependancyData);
//        $el->appendChild($field);
//        $comment = new DOMComment("NEWLINE");
//        $el->appendChild($comment);
//
//    }
//
//}
//
//function jsonToXML(&$doc, &$el, $data, $dependancyData){
//    foreach($data as $key=>$val){
//        $key = preg_replace('/\[([^\]]+)\]/ies', "\$dependancyData['\\1'];", $key);
//
//        if(is_scalar($val)){
//            if($val === true){
//                $val = 'true';
//            }elseif($val === false){
//                $val = 'false';
//            }
//
//
//            if($key != 'pattern'){
//                $val = preg_replace('/\[([^\]]+)\]/ies', "\$dependancyData['\\1'];", $val);
//            }
//
//            if($val !== null){
//                $el->setAttribute($key, $val);
//            }
//        }elseif(is_array($val)){
//            foreach($val as $subval){
//                $subel = $doc->createElement($key);
//                jsonToXML($doc, $subel, $subval, $dependancyData);
//                $el->appendChild($subel);
//            }
//        }else{
//            $subel = $doc->createElement($key);
//            jsonToXML($doc, $subel, $val, $dependancyData);
//            $el->appendChild($subel);
//        }
//
//    }
//}

