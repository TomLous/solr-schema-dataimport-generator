<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     16/06/15 11:25
 */

print PHP_EOL . "------------------------------------" . PHP_EOL . "Generating dataimport.xml" . PHP_EOL;
include_once('utils.php');
// Make sure to escape all slashes in Json eg"pattern": "([\.,;:-_])", => "pattern": "([\\.,;:-_])",
$config = json_decode(file_get_contents("config/config.json"), true);

if (json_last_error()) {
    print "json decode error: " . json_last_error_msg();
    exit();
}

$dataimportDoc = new DOMDocument("1.0", "UTF-8");
$dataimportDoc->preserveWhiteSpace = true;
$dataimportDoc->formatOutput = true;

$dataConfig = $dataimportDoc->createElement('dataConfig');


$mainDataSource = null;
foreach ($config['dataSources'] as $name => $data) {
    $dataSource = $dataimportDoc->createElement('dataSource');
    $dataSource->setAttribute('name', $name);
    if ($mainDataSource === null) {
        $mainDataSource = $name;
    }
    foreach ($data as $key => $val) {
        $dataSource->setAttribute($key, $val);
    }
    $dataConfig->appendChild($dataSource);
}

$dependencyVariables = array();
if (isset($config['dependencyVariables'])) {
    $dependencyVariables = $config['dependencyVariables'];
}

$document = $dataimportDoc->createElement('document');
$dataConfig->appendChild($document);



$fields = $config['fields'];
$entityQueries = $config['entityQueries'];

// fields generation

createQueriesConfig($dataimportDoc, $document, $fields, $entityQueries, $dependencyVariables);


//$dataSource->appendChild($document);


// parsing XML creating readable dataimport.xml
$dataimportDoc->appendChild($dataConfig);

$xml = $dataimportDoc->saveXML();
$xml = preg_replace("/\s+<!--NEWLINE-->/is", "\n", $xml);
$xml = preg_replace("/&#10;/is", "\n", $xml);
$xml = preg_replace("/&#9;/is", "\t", $xml);
//print $xml;

file_put_contents('target/dataimport.xml', $xml);


function createQueriesConfig(&$doc, &$el, $fields, $entityQueries, $dependencyVariables)
{

    // select
    $selectFields = array();
    $primaryKeys = array();
    $importFields = array();
    foreach ($fields as $fieldName => $fieldInfo) {
        $dependancyArray = createDependencyArray($fieldInfo, $dependencyVariables);
        foreach ($dependancyArray as $pos => $dependancyData) {
//            $sourceFieldName = $fieldName;
            $currentFieldName = dependencyParser($fieldName, $dependancyData);


            if (isset($fieldInfo['dataSourceEntity'])) {

                if (isset($fieldInfo['uniqueKey']) && $fieldInfo['uniqueKey']) {
                    $primaryKeys[$fieldInfo['dataSourceEntity']] = $currentFieldName;
                }

                if (!isset($selectFields[$fieldInfo['dataSourceEntity']])) {
                    $selectFields[$fieldInfo['dataSourceEntity']] = array();
                    $importFields[$fieldInfo['dataSourceEntity']] = array();
                }
                $statement = isset($fieldInfo['dataSourceStatement']) ? $fieldInfo['dataSourceStatement'] : "`{$currentFieldName}`";

                if (isset($fieldInfo['dataSourceStatementDependencyMapping'])) {
                    $mapping = array();
                    foreach ($dependancyData as $key => $val) {
                        if (isset($fieldInfo['dataSourceStatementDependencyMapping'][$key]) && isset($fieldInfo['dataSourceStatementDependencyMapping'][$key][$val])) {
                            $mapping[$key . '_map'] = $fieldInfo['dataSourceStatementDependencyMapping'][$key][$val];
                        }
                    }
                    $statement = dependencyParser($statement, $mapping);

                }


                if (isset($fieldInfo['multiValued']) && $fieldInfo['multiValued']) {
                    $statement .= " as `{$currentFieldName}`";
                } else {
                    $statement .= " as `{$currentFieldName}`";
                }
                $selectFields[$fieldInfo['dataSourceEntity']][$currentFieldName] = $statement;

                if (isset($fieldInfo['dataSourceMultivaluedSeperator'])){
                    $importFields[$fieldInfo['dataSourceEntity']][$currentFieldName] = array('column' => $currentFieldName, 'sourceColName' => $currentFieldName, 'splitBy'=>$fieldInfo['dataSourceMultivaluedSeperator']);

                } else {
                    $importFields[$fieldInfo['dataSourceEntity']][$currentFieldName] = array('column' => $currentFieldName, 'name' => $currentFieldName);
                }

            }


        }
    }



    // from
    $entitiesXML = array();
    $tabs = str_repeat("\t", 15);
    $tabs2 = str_repeat("\t", 14);
    $tabs3 = str_repeat("\t", 13);
    foreach ($entityQueries as $entityName => $queryInfo) {
        $entity = $doc->createElement('entity');
        $entity->setAttribute('name', $entityName);
        if (isset($queryInfo['dataSource'])) {
            $entity->setAttribute('dataSource', $queryInfo['dataSource']);
        }
        if (isset($queryInfo['transformers'])) {
            $entity->setAttribute('transformer', implode(",", $queryInfo['transformers']));
        }
        if(isset($queryInfo['pk'])){
            $entity->setAttribute('pk', $queryInfo['pk']);
        }elseif(isset($primaryKeys[$entityName])){
            $entity->setAttribute('pk', $primaryKeys[$entityName]);
        }



        foreach ($queryInfo['queries'] as $queryType => $queryTypeData) {
            $query = "SELECT ";
            $select = array();
            if(isset($queryTypeData['fields'])){
                foreach($queryTypeData['fields'] as $fieldName){
                    $select[] = $selectFields[$entityName][$fieldName];
                }
            }else{
                $select = $selectFields[$entityName];
            }
            $query .= "\n{$tabs}".implode(",\n{$tabs}", $select);

            foreach($queryTypeData['tables'] as $tableGroup){
                $query .= " \n{$tabs2}".implode(" \n{$tabs}",$queryInfo['tables'][$tableGroup]);
            }
            $query .= " \n{$tabs2}".$queryTypeData['filter'].";\n{$tabs3}";

            $entity->setAttribute($queryType, $query);
        }


        $entitiesXML[$entityName] = array('element'=>$entity);
        if(isset($queryInfo['parentEntity'])){
            $entitiesXML[$entityName]['parent'] = $queryInfo['parentEntity'];
        }



    }




    // fields

    foreach($importFields as $entityName => $data){
        $parentEl = $entitiesXML[$entityName]['element'];
        foreach($data as $fieldName=>$attributes){
            $field = $doc->createElement('field');
            foreach($attributes as $attr=>$val){
                $field->setAttribute($attr, $val);
            }
            $parentEl->appendChild($field);
        }


    }

    foreach($entitiesXML as $entityName=>$entityData){
        if(!isset($entityData['parent'])){
            $el->appendChild($entityData['element']);
        }else{
            $entitiesXML[$entityData['parent']]['element']->appendChild($entityData['element']);
        }
    }


}
