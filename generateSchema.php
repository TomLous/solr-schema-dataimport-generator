<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     15/06/15 22:31
 */
print PHP_EOL."------------------------------------".PHP_EOL."Generating schema.xml".PHP_EOL;

include_once('utils.php');

// Make sure to escape all slashes in Json eg"pattern": "([\.,;:-_])", => "pattern": "([\\.,;:-_])",
$config = json_decode(file_get_contents("config/config.json"), true);

if(json_last_error()){
    "json decode error: " . json_last_error_msg();
    exit();
}

$schemaDoc = new DOMDocument("1.0","UTF-8");
$schemaDoc->preserveWhiteSpace = true;
$schemaDoc->formatOutput = true;
$schema = $schemaDoc->createElement('schema');


// parse config
if(isset($config['name'])){
    $schema->setAttribute('name', $config['name']);
    unset($config['name']);
}

if(isset($config['solrSchemaVersion'])){
    $schema->setAttribute('version', $config['solrSchemaVersion']);
    unset($config['solrSchemaVersion']);
}

$fields = $config['fields'];
unset($config['fields']);

$fieldTypes = $config['fieldTypes'];
unset($config['fieldTypes']);

$customVariables = $config;

$foundTypes = array();
$unusedTypes = array();

// fields generation
foreach($fields as $fieldName=>$fieldInfo){
    $dependancyArray = array(array());
    if(isset($fieldInfo['dependency'])){
        $combinedArrays = array();
        foreach($fieldInfo['dependency'] as $dependancyPlaceholder=>$dependancyVariable){
            if($customVariables[$dependancyVariable]){
                $combinedArrays[$dependancyPlaceholder] = $customVariables[$dependancyVariable];
            }
        }
        $dependancyArray = arrayCartesian($combinedArrays);
    }
    createFieldConfig($schemaDoc, $schema, $fieldName, $fieldInfo,$dependancyArray);
}

// fieldTypes generation
// fields generation
foreach($fieldTypes as $fieldType=>$fieldTypeInfo){
    $dependancyArray = array(array());
    if(isset($fieldTypeInfo['dependency'])){
        $combinedArrays = array();
        foreach($fieldTypeInfo['dependency'] as $dependancyPlaceholder=>$dependancyVariable){
            if($customVariables[$dependancyVariable]){
                $combinedArrays[$dependancyPlaceholder] = $customVariables[$dependancyVariable];
            }
        }
        $dependancyArray = arrayCartesian($combinedArrays);
        unset($fieldTypeInfo['dependency']);
    }
    createFieldTypeConfig($schemaDoc, $schema, $fieldType, $fieldTypeInfo,$dependancyArray);
}


// parsing XML creating readable schema.xml
$schemaDoc->appendChild($schema);

$xml = $schemaDoc->saveXML();
$xml = preg_replace("/\s+<!--NEWLINE-->/is","\n",$xml);
//print $xml;

file_put_contents('target/schema.xml', $xml);

print "missing fieldTypes:".PHP_EOL;
print_r($foundTypes);
print "unused fieldTypes:".PHP_EOL;
print_r($unusedTypes);






// methods
function createFieldConfig(&$doc, &$el, $fieldName,  $fieldInfo, $dependancyArray){
    global $foundTypes;
    foreach($dependancyArray as $pos=>$dependancyData){
        $sourceFieldName = $fieldName;


        foreach($fieldInfo['types'] as $fieldNamePostfix=>$fieldType){
            $isSourceField = false;
            $currentFieldName = $fieldName;
            if($fieldNamePostfix == '_'){
                $isSourceField = true;
            }else{
                $currentFieldName .= '_'.$fieldNamePostfix;
            }

            $currentFieldName = preg_replace('/\[([^\]]+)\]/ies', "\$dependancyData['\\1'];", $currentFieldName);
            $currentFieldType = preg_replace('/\[([^\]]+)\]/ies', "\$dependancyData['\\1'];", $fieldType);

            $foundTypes[$currentFieldType] = $currentFieldType;
            if($isSourceField){
                $sourceFieldName = $currentFieldName;
                $comment = new DOMComment(" $currentFieldName ");
                $el->appendChild($comment);
            }

            $indexed = isset($fieldInfo['indexed'])?$fieldInfo['indexed']:true;
            $stored = isset($fieldInfo['stored'])&& $isSourceField?$fieldInfo['stored']:false;
            $required = isset($fieldInfo['required'])&& $isSourceField?$fieldInfo['required']:false;
            $multiValued = isset($fieldInfo['multiValued'])?$fieldInfo['multiValued']:null;
            $docValues = isset($fieldInfo['docValues'])?$fieldInfo['docValues']:null;
            $omitNorms = isset($fieldInfo['omitNorms'])?$fieldInfo['omitNorms']:null;
            $termVectors = isset($fieldInfo['termVectors'])&& $isSourceField?$fieldInfo['termVectors']:null;
            $termPositions = isset($fieldInfo['termPositions'])&& $isSourceField?$fieldInfo['termPositions']:null;
            $termOffsets = isset($fieldInfo['termOffsets'])&& $isSourceField?$fieldInfo['termOffsets']:null;
            $default = isset($fieldInfo['default'])&& $isSourceField?$fieldInfo['default']:null;

            $field = $doc->createElement('field');
            $field->setAttribute("name", $currentFieldName);
            $field->setAttribute('type', $currentFieldType);
            $field->setAttribute('indexed', $indexed?'true':'false');
            $field->setAttribute('stored', $stored?'true':'false');
            $field->setAttribute('required', $required?'true':'false');
            if($multiValued !== null){
                $field->setAttribute('multiValued', $multiValued?'true':'false');
            }
            if($docValues !== null){
                $field->setAttribute('docValues', $docValues?'true':'false');
            }
            if($omitNorms !== null){
                $field->setAttribute('omitNorms', $omitNorms?'true':'false');
            }
            if($termVectors !== null){
                $field->setAttribute('termVectors', $termVectors?'true':'false');
            }
            if($termPositions !== null){
                $field->setAttribute('termPositions', $termPositions?'true':'false');
            }
            if($termOffsets !== null){
                $field->setAttribute('termOffsets', $termOffsets?'true':'false');
            }
            if($default !== null){
                $field->setAttribute('default', $default);
            }
            $el->appendChild($field);
            if(!$isSourceField){
                $field = $doc->createElement('copyField');
                $field->setAttribute('source', $sourceFieldName);
                $field->setAttribute('dest', $currentFieldName);
                $el->appendChild($field);

            }

        }
        $comment = new DOMComment("NEWLINE");
        $el->appendChild($comment);

        if(isset($fieldInfo['uniqueKey'])){
            $field = $doc->createElement('uniqueKey', $sourceFieldName);
            $el->appendChild($field);
            $comment = new DOMComment("NEWLINE");
            $el->appendChild($comment);
        }
        if(isset($fieldInfo['defaultSearchField'])){
            $field = $doc->createElement('defaultSearchField', $sourceFieldName);
            $el->appendChild($field);
            $comment = new DOMComment("NEWLINE");
            $el->appendChild($comment);
        }
    }
}

function createFieldTypeConfig(&$doc, &$el, $fieldType,  $fieldTypeInfo, $dependancyArray){
    global $foundTypes;
    global $unusedTypes;

    foreach($dependancyArray as $pos=>$dependancyData){
        $currentFieldType = preg_replace('/\[([^\]]+)\]/ies', "\$dependancyData['\\1'];", $fieldType);

        if(isset($foundTypes[$currentFieldType])){
            unset($foundTypes[$currentFieldType]);
        }else{
            $unusedTypes[$currentFieldType] = $currentFieldType;
        }

        $comment = new DOMComment(" $currentFieldType ");
        $el->appendChild($comment);

        $field = $doc->createElement('fieldType');

        jsonToXML($doc, $field, $fieldTypeInfo, $dependancyData);
        $el->appendChild($field);
        $comment = new DOMComment("NEWLINE");
        $el->appendChild($comment);

    }

}

