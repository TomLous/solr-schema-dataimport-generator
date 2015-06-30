<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     16/06/15 11:25
 */

print PHP_EOL . "------------------------------------" . PHP_EOL . "Generating solr-api-config.json" . PHP_EOL;
include_once('utils.php');
// Make sure to escape all slashes in Json eg"pattern": "([\.,;:-_])", => "pattern": "([\\.,;:-_])",
$config = json_decode(file_get_contents("config/config.json"), true);

if (json_last_error()) {
    print "json decode error: " . json_last_error_msg();
    exit();
}

$dependencyVariables = array();
if(isset($config['dependencyVariables'])){
    $dependencyVariables = $config['dependencyVariables'];
}

$fields = $config['fields'];

$apiConfig = $config['searchOptions'];



//$fieldTypes = $config['fieldTypes'];

foreach($apiConfig as $action => $data ){
    $apiConfig[$action]['dependencies'] = array();
    $apiConfig[$action]['fields'] = array();
    $apiConfig[$action]['queryFields'] = array();
    $apiConfig[$action]['phraseFields'] = array();
    $apiConfig[$action]['phraseBiGramFields'] = array();
    $apiConfig[$action]['phraseTriGramFields'] = array();
    if($data['facets']){
        $apiConfig[$action]['facetFields'] = array();
    }
    $apiConfig[$action]['dependencyFields'] = array();
}

// fields generation
foreach($fields as $fieldName=>$fieldInfo){
    $dependancyArray = createDependencyArray($fieldInfo, $dependencyVariables);
    createAPIConfig($apiConfig, $fieldName, $fieldInfo,$dependancyArray);
}

foreach($apiConfig as $action => $data ){
    $apiConfig[$action]['dependencies']  = array_values($apiConfig[$action]['dependencies']);
}

$apiConfig['__meta']=array('comment'=>"Generated ".date('Y-m-d')." with Solr Schema Generator (https://github.com/TomLous/solr-schema-dataimport-generator)");
file_put_contents('target/apiconfig.json', jsonFormat($apiConfig));

// methods
function createAPIConfig(&$conf, $fieldName,  $fieldInfo, $dependancyArray){
    global $config;

    if(isset($fieldInfo['searchOptions'])){
        foreach($dependancyArray as $pos=>$dependancyData){
            $actions = $fieldInfo['searchOptions']['actions'];

            foreach($fieldInfo['types'] as $fieldNamePostfix=>$fieldType){
                foreach($actions as $action){
                    $isSourceField = false;
                    $currentFieldName = $fieldName;
                    if($fieldNamePostfix == '_'){
                        $isSourceField = true;
                    }else{
                        $currentFieldName .= '_'.$fieldNamePostfix;
                    }

                    $currentFieldName = dependencyParser($currentFieldName, $dependancyData);
                    $currentFieldType = dependencyParser($fieldType, $dependancyData);

                    $dependancyConf = false;
                    if(isset($fieldInfo['__dependency'])){
                        $dependancyConf = array();

                        $idxa = array();

                        foreach($fieldInfo['__dependency'] as $key=>$dep){
                            $dependancyConf[$key] = $dependancyData[$key];
                            $idxa[] = $key.'_'.$dependancyData[$key];
                        }
                        sort($idxa);
                        $conf[$action]['dependencies'][implode('_',$idxa)] = $dependancyData;
                    }

//                    $apiConfig[$action]['dependencyVariables'] = $config['dependencyVariables'];

                    $defaults = array('search'=>false,'return'=>false,'facet'=>false, 'boost'=>0, 'fuzzy'=>false, 'additional'=>array(), 'phraseField'=>false, 'phraseFieldBiGram'=>false, 'phraseFieldTriGram'=>false);
                    $fieldData = array_merge($defaults, $fieldInfo['searchOptions'], $dependancyData);

                    $fieldTypeData = $config['fieldTypes'][$fieldType];

                    if(isset($fieldTypeData['__searchPhraseField'])){
                        $fieldData['phraseField'] = min($fieldData['phraseField'], $fieldTypeData['__searchPhraseField']);
                        $fieldData['phraseFieldBiGram'] = min($fieldData['phraseFieldBiGram'], $fieldTypeData['__searchPhraseField']);
                        $fieldData['phraseFieldTriGram'] = min($fieldData['phraseFieldTriGram'], $fieldTypeData['__searchPhraseField']);
                    }

                    if(isset($fieldTypeData['__searchBoostFactor'])){
                        $fieldData['boost'] = ceil($fieldData['boost'] * $fieldTypeData['__searchBoostFactor']);
                    }

                    if(isset($fieldTypeData['__searchBoostValue'])){
                        $fieldData['boost'] = $fieldTypeData['__searchBoostValue'];
                    }

                    if(isset($fieldTypeData['__searchFuzzyFactor'])){
                        $fieldData['fuzzy'] = ceil($fieldData['fuzzy'] * $fieldTypeData['__searchFuzzyFactor']);
                    }

                    if(isset($fieldTypeData['__searchFuzzyValue'])){
                        $fieldData['fuzzy'] = $fieldTypeData['__searchFuzzyValue'];
                    }

                    // set fields

                    if($fieldData['return'] && isset($conf[$action]['fields'])){
                        $conf[$action]['fields'][$currentFieldName] = array('field'=>$currentFieldName,'fuzzy'=>$fieldData['fuzzy']);
                    }

                    if($fieldData['search'] && $fieldData['boost'] && isset($conf[$action]['queryFields'])){
                        $statement = $currentFieldName . ((is_numeric($fieldData['boost']) && $fieldData['boost']!=1)?"^".ceil($fieldData['boost']):"");
                        $conf[$action]['queryFields'][$currentFieldName] = array('field'=>$currentFieldName, 'boost'=>$fieldData['boost'], 'statement'=>$statement);

                    }

                    if($fieldData['phraseField'] && isset($conf[$action]['phraseFields'])){
                        $phraseBoost = isset($fieldData['boost']) ? $fieldData['boost'] : 1;
                        $phraseBoost *= is_numeric($fieldData['phraseField'])?intval($fieldData['phraseField']):1;
                        $phraseBoost = ceil($phraseBoost);
                        $statement = $currentFieldName . "^".$phraseBoost;
                        $conf[$action]['phraseFields'][$currentFieldName] = array('field'=>$currentFieldName, 'boost'=>$phraseBoost, 'statement'=>$statement);
                    }

                    if($fieldData['phraseFieldBiGram'] && isset($conf[$action]['phraseBiGramFields'])){
                        $phraseBoost = isset($fieldData['boost']) ? $fieldData['boost'] : 1;
                        $phraseBoost *= is_numeric($fieldData['phraseFieldBiGram'])?intval($fieldData['phraseFieldBiGram']):1;
                        $phraseBoost = ceil($phraseBoost);
                        $statement = $currentFieldName . "^".$phraseBoost;
                        $conf[$action]['phraseBiGramFields'][$currentFieldName] = array('field'=>$currentFieldName, 'boost'=>$phraseBoost, 'statement'=>$statement);
                    }

                    if($fieldData['phraseFieldTriGram'] && isset($conf[$action]['phraseTriGramFields'])){
                        $phraseBoost = isset($fieldData['boost']) ? $fieldData['boost'] : 1;
                        $phraseBoost *= is_numeric($fieldData['phraseFieldTriGram'])?intval($fieldData['phraseFieldTriGram']):1;
                        $phraseBoost = ceil($phraseBoost);
                        $statement = $currentFieldName . "^".$phraseBoost;
                        $conf[$action]['phraseTriGramFields'][$currentFieldName] = array('field'=>$currentFieldName, 'boost'=>$phraseBoost, 'statement'=>$statement);
                    }

                    if($isSourceField && $fieldData['facet']  && $conf[$action]['facets'] && isset($conf[$action]['facetFields'])){
                        $conf[$action]['facetFields'][$currentFieldName] = array('field'=>$currentFieldName);
                    }

                    if($dependancyConf && isset($conf[$action]['dependencyFields'])){
                        $conf[$action]['dependencyFields'][$currentFieldName] = $dependancyConf;
                    }

                    foreach($fieldData['additional'] as $key=>$opt){
                        if($opt == "stats.field"){
                            if(!isset($conf[$action]['options'][$opt])){
                                $conf[$action]['options'][$opt]= array();
                            }
                            $conf[$action]['options'][$opt][] = $currentFieldName;

                        }
                    }


                }



            }

        }
    }
}