#Solr Config Generator
These php scripts generate 3 config files 

- **generateSchema.php** => schema.xml [see](https://wiki.apache.org/solr/SchemaXml)
- **generateDataimport.php** => dataimport-config.xml [see](https://wiki.apache.org/solr/DataImportHandler)
- **generatePHPAPIConfig.php** => apiconfig.json (custom format)

based on 1 central [config.json](config/config.default.json) for use in Solr Core & API

-----
## Table of Contents
- [config.json](#config)
	- [fields](#configfields)
		- [searchOptions](#configfieldsearchoptions)
	- [fieldTypes](#configfieldtypes)
	- [dataSources](#configdatasources)
	- [entityQueries](#configentityqueries)
	- [searchOptions](#configsearchoptions)
	- [dependencyVariables](#configdependencyvariables) 

-----
##<a name="config"></a>config.json

1. Copy the [config/config.default.json](config/config.default.json) to [config/config.json](config/config.json) or create new json

The config.json consits of several required properties:

- **name** : The name of the schema (not very important, but should be equivilent to the solr core name)
- **solrSchemaVersion** : This should be equivilent to the current used schema [doc](https://wiki.apache.org/solr/SchemaXml#Schema_version_attribute_in_the_root_node), currently **1.5**
- **fields** : fields in core, [detailed config](#configfields)
- **fieldTypes** : field types in core, [detailed config](#configfieldtypes)
- **dataSources** : datasources for data-import, [detailed config](#configdatasources) 
- **entityQueries** : entity definition & SQL query generator syntax, [detailed config](#configentityqueries) 
- **searchOptions** : search options per search action type, [detailed config](#configsearchoptions) 
- **dependencyVariables** : dependacy field, for generating & queries, [detailed config](#configdependencyvariables) 

```
{
	name: "outlets",
	solrSchemaVersion: "1.5",
	dependencyVariables: {...},
	dataSources: {...},
	entityQueries: {...},
	searchOptions: {...},
	fields: {...},
	fieldTypes: {...}
}
```
-----
####<a name="configfields"></a>fields
**fields** consists of a list of fieldnames with a config object encapsulated.
All parts of the config can be templated for [dependancyVariables]

Properties of a specific set of properties

***required***:

- **dataSourceEntity** : The name of the entity this field belongs to. [see](#configentityqueries)
- **dataSourceStatement** : The SELECT part of the SQL-query for this field. Can be a complext statement or just a fieldname (don't forget the table prefix for ambiguity)
- **types** : An array of fieldTypes for this field. 
	- The fieldname is postfixed ([fieldname]_[key]) for each field type. The key is arbitrary, but the value should match an actual [fieldType](#configfieldtypes). 
	- Special case is the key **'_'** which represent no postfix ([fieldname]) and should always be present
	- The values to the other types are copied with a [copyField](https://cwiki.apache.org/confluence/display/solr/Copying+Fields) from the **_** (source field)
- **searchOptions** : Config for searching on this field (for apiconfig) [see](#configfieldsearchoptions)

***optional***:

- **__dependency** : An optional key value array, that is used to generate multiple fields based on a dependency.
	- All field properties and names are matched for the string '[key]' and linked to the name of the mapping declared in [dependencyVariables](#configdependencyvariables)
	- e.g. a fieldname, named name_[lang] is mapped by a config {"lang"=>"language_codes"} to the array defined in 
- 	dependencyVariables with the key "language_codes", generating for each defined "language_code" a field (& field sub types)
- **dataSourceStatementDependencyMapping** : an optional mapping between a __dependency key and it's inherited values (from dependencyVariables) and a SQL placeholder for the [{key}\_map] variable
- **uniqueKey** : true/false, Solr specific property for a field, [https://wiki.apache.org/solr/UniqueKey](https://wiki.apache.org/solr/UniqueKey) 
- **indexed** : true/false, Solr specific property for a field, [https://wiki.apache.org/solr/SchemaXml#Common_field_options](https://wiki.apache.org/solr/SchemaXml#Common_field_options)
- **stored** : true/false, Solr specific property for a field, [https://wiki.apache.org/solr/SchemaXml#Common_field_options](https://wiki.apache.org/solr/SchemaXml#Common_field_options)
- **required** : true/false, Solr specific property for a field, [https://wiki.apache.org/solr/SchemaXml#Common_field_options](https://wiki.apache.org/solr/SchemaXml#Common_field_options)
- **multiValued** : true/false, Solr specific property for a field, [https://wiki.apache.org/solr/SchemaXml#Common_field_options](https://wiki.apache.org/solr/SchemaXml#Common_field_options)
- **dataSourceMultivaluedSeperator** : String used to split the SQL-query result for this field into the multivalued field (multiValued should be true!)
- **_comment** : A comment string for the schema.xml

*** <= config.json***

```
fieldname_[lang] : {
	_comment: "Some demo field",
	uniqueKey: false,
	types: {
		_: "string",
		suggest: "text_[lang]_splitting",
		edge: "autocomplete_edge",
		ngram: "autocomplete_ngram",
		reverse: "text_general_reversed"
	},
	__dependency: {
		lang: "language_codes"
	},
	indexed: true,
	stored: true,
	required: true,
	multiValued: true,
    dataSourceStatement: "GROUP_CONCAT(DISTINCT kt_type.waarde_[lang_map] SEPARATOR '|')",
    dataSourceMultivaluedSeperator: "|",
	dataSourceStatementDependencyMapping: {
		lang: {
			en: "english",
			nl: "dutch",
			de: "deutsch",
		}
	},
	dataSourceEntity: "mainentity",
	searchOptions: {...}
}	
...
dependencyVariables: {
	language_codes: [
		"en",
		"nl",
		"de"
		]
	}
```

*** => schema.xml***

```
...
<! -- fieldname_en:  Some demo field -->
  <field name="fieldname_en" type="string" indexed="true" stored="true" required="true" multiValued="true"/>
  <field name="fieldname_en_suggest" type="text_en_splitting" indexed="true" stored="false" required="false" multiValued="true"/>
  <copyField source="fieldname_en" dest="fieldname_en_suggest"/>
  <field name="fieldname_en_edge" type="autocomplete_edge" indexed="true" stored="false" required="false" multiValued="true"/>
  <copyField source="fieldname_en" dest="fieldname_en_edge"/>
  <field name="fieldname_en_ngram" type="autocomplete_ngram" indexed="true" stored="false" required="false" multiValued="true"/>
  <copyField source="fieldname_en" dest="fieldname_en_ngram"/>
  <field name="fieldname_en_reverse" type="text_general_reversed" indexed="true" stored="false" required="false" multiValued="true"/>
  <copyField source="fieldname_en" dest="fieldname_en_reverse"/>
  ...
```

*** => dataimport-config.xml***

```
...
SELECT ... GROUP_CONCAT(DISTINCT kt_type.waarde_english SEPARATOR '|') as fieldname_en, ...
...

 <field column="fieldname_en" sourceColName="fieldname_en" splitBy="|"/>
 <field column="fieldname_nl" sourceColName="fieldname_nl" splitBy="|"/>
 <field column="fieldname_de" sourceColName="fieldname_de" splitBy="|"/>
```




#####<a name="configfieldsearchoptions"></a>*[field]* searchOptions
Field's search options are used for apiconfig.json for querying fields


- **search** : true/false whether this field should be searched on (has to be indexed: true) [qf](https://wiki.apache.org/solr/ExtendedDisMax#qf_.28Query_Fields.29)
- **return** : true/false whether this field should be returned (has to be stored: true) [fl](https://wiki.apache.org/solr/CommonQueryParameters#fl)
- **facet** : whether the sourcefield (not the copied fields), should be a facet [facet](https://wiki.apache.org/solr/SimpleFacetParameters#facet)
- **actions** : list of search actions, in which this field should be considered. Has to correspond with keys in global [searchOptions](#configsearchoptions)
- **boost** : base boostValue for this field, can/will be modified by each fieldType & individual phraseQueries [field^boost](https://wiki.apache.org/solr/ExtendedDisMax#qf_.28Query_Fields.29)
- **fuzzy** : Levensthein Distance to search for in the term [term~fuzzy](https://lucene.apache.org/core/2_9_4/queryparsersyntax.html#Fuzzy Searches)
- **phraseField** : true/false/int whether it should be used as phraseField and if true: factor = 1, if int: the multiplier is used on **boost** [pf](https://wiki.apache.org/solr/ExtendedDisMax#pf_.28Phrase_Fields.29)
- **phraseFieldBiGram** : same as phraseField [pf2](https://wiki.apache.org/solr/ExtendedDisMax#pf2_.28Phrase_bigram_fields.29)
- **additional** : array of custom addition actions. For now only stats.field & stats.facet are supported

*** <= config.json***
 
```
searchOptions: {
	search: true,
	return: true,
	actions: [
		"search",
		"autocomplete",
		"location"
	],
	facet: false,
	boost: 100,
	fuzzy: 4,
	phraseField: 4,
	phraseFieldBiGram: 3,
	phraseFieldTriGram: 2,
	additional: ["stats.field"]
}
```

*** => api-config.json***

```
fields{
...
,
      "fieldname_en": {
        "field": "fieldname_en",
        "fuzzy": 4,
        "main" : true
      },
      "fieldname_en_suggest": {
        "field": "fieldname_en_suggest",
        "fuzzy": false,
        "main" : false
      },
      "fieldname_en_edge": {
        "field": "fieldname_en_edge",
        "fuzzy": false,
        "main" : false
      },
      "fieldname_en_ngram": {
        "field": "fieldname_en_ngram",
        "fuzzy": false,
        "main" : false
      },
      "fieldname_en_reverse": {
        "field": "fieldname_en_reverse",
        "fuzzy": 2,
        "main" : false
      },    
,
queryFields: {             
...
	 "fieldname_en": {
        "field": "fieldname_en",
        "boost": 10,
        "statement": "fieldname_en^10"
      },
      "fieldname_en_suggest": {
        "field": "fieldname_en_suggest",
        "boost": 8,
        "statement": "fieldname_en_suggest^8"
      },
      ...
      
```

-----
####<a name="configfieldtypes"></a>fieldTypes
FieldTypes are generated from the json into xml for the schema.xml as is. Structure, fields and values are preserved.
Exception to this rule are all fieldType properties prefixed with __


- **__dependency** : An optional key value array, that is used to generate multiple fieldTypes based on a dependency.
	- All field properties and names are matched for the string '[key]' and linked to the name of the mapping declared in [dependencyVariables](#configdependencyvariables)
	- e.g. a fieldType, named text_[lang]_splitting is mapped by a config {"lang"=>"language_codes"} to the array defined in 
dependencyVariables with the key "language_codes", generating for each defined "language_code" a fieldType
- **__searchBoostFactor** : float, A factor applied to the boost property of a field's searchOptions
- **__searchBoostValue** : float, A value replacing  he boost property of a field's searchOptions (if not false)
- **__searchFuzzyFactor** : float, A factor applied to the fuzzy property of a field's searchOptions
- **__searchFuzzyValue** : float, A value replacing  he fuzzy property of a field's searchOptions (if not false)
- **__searchPhraseField** : true/false, whether a field of this type can be considered as phrasefield


*** <= config.json***

```
...
boolean: {
	class: "solr.BoolField",
	sortMissingLast: true,
	__searchPhraseField: false
},
...
text_[lang]_splitting: {
	class: "solr.TextField",
	positionIncrementGap: 100,
	autoGeneratePhraseQueries: true,
	__dependency: {
		lang: "language_codes"
	},
	analyzer: {
		index: {
			charFilter: [{
				class: "solr.MappingCharFilterFactory",
				mapping: "mapping-ISOLatin1Accent.txt"
			}],
			tokenizer: [{
				class: "solr.WhitespaceTokenizerFactory"
			}],
			filter: [{
				class: "solr.StopFilterFactory",
				ignoreCase: true,
				words: "lang/stopwords_[lang].txt"
			},{
				class: "solr.WordDelimiterFilterFactory",
				generateWordParts: 1,
				generateNumberParts: 1,
				catenateWords: 1,
				catenateNumbers: 1,
				catenateAll: 0,
				splitOnCaseChange: 1
			},{
				class: "solr.LowerCaseFilterFactory"
			},{
				class: "solr.PorterStemFilterFactory"
			}]},
		query: {
			charFilter: [{
				class: "solr.MappingCharFilterFactory",
				mapping: "mapping-ISOLatin1Accent.txt"
			}],
			tokenizer: [{
				class: "solr.WhitespaceTokenizerFactory"
			}],
			filter: [{
				class: "solr.SynonymFilterFactory",
				synonyms: "synonyms.txt",
				ignoreCase: true,
				expand: true
			},{
				class: "solr.StopFilterFactory",
				ignoreCase: true,
				words: "lang/stopwords_[lang].txt"
			},{
				class: "solr.WordDelimiterFilterFactory",
				generateWordParts: 1,
				generateNumberParts: 1,
				catenateWords: 0,
				catenateNumbers: 0,
				catenateAll: 0,
				splitOnCaseChange: 1
			},{
				class: "solr.LowerCaseFilterFactory"
			},{
				class: "solr.PorterStemFilterFactory"
			}]
		}
	},
	__searchBoostFactor: 0.8,
	__searchFuzzyValue: false
},
...

```

*** => schema.xml***

```
<! -- boolean -->
<fieldType name="boolean" class="solr.BoolField" sortMissingLast="true"/>

<! -- text_en_splitting -->
  <fieldType name="text_en_splitting" class="solr.TextField" positionIncrementGap="100" autoGeneratePhraseQueries="true">
    <analyzer type="index">
      <charFilter class="solr.MappingCharFilterFactory" mapping="mapping-ISOLatin1Accent.txt"/>
      <tokenizer class="solr.WhitespaceTokenizerFactory"/>
      <filter class="solr.StopFilterFactory" ignoreCase="true" words="lang/stopwords_en.txt"/>
      <filter class="solr.WordDelimiterFilterFactory" generateWordParts="1" generateNumberParts="1" catenateWords="1" catenateNumbers="1" catenateAll="0" splitOnCaseChange="1"/>
      <filter class="solr.LowerCaseFilterFactory"/>
      <filter class="solr.PorterStemFilterFactory"/>
    </analyzer>
    <analyzer type="query">
      <charFilter class="solr.MappingCharFilterFactory" mapping="mapping-ISOLatin1Accent.txt"/>
      <tokenizer class="solr.WhitespaceTokenizerFactory"/>
      <filter class="solr.SynonymFilterFactory" synonyms="synonyms.txt" ignoreCase="true" expand="true"/>
      <filter class="solr.StopFilterFactory" ignoreCase="true" words="lang/stopwords_en.txt"/>
      <filter class="solr.WordDelimiterFilterFactory" generateWordParts="1" generateNumberParts="1" catenateWords="0" catenateNumbers="0" catenateAll="0" splitOnCaseChange="1"/>
      <filter class="solr.LowerCaseFilterFactory"/>
      <filter class="solr.PorterStemFilterFactory"/>
    </analyzer>
  </fieldType>
  
```
-----
####<a name="configdatasources"></a>dataSources
This section covers the configuration of datasources.
Of course solrconfig.xml has to be configured for use of these datasources
As for now only Jdbc data sources have been tested, but in theory it should support different kinds as well.

Each (jdbc) data source is identified by a key (for reference in [entityQueries](#configentityqueries) ) and sees all properies as attributes for in dataimport-config.xml.

e.g. for [JDBC/MySQL](https://wiki.apache.org/solr/DataImportHandler#Usage_with_RDBMS) (only working test case)

*** <= config.json***

```
db1: {
	type: "JdbcDataSource",
	driver: "com.mysql.jdbc.Driver",
	url: "jdbc:mysql://[host]/[db]",
	user: "[user]",
	password: "[password]",
	batchSize: -1
},
```

*** => dataimport-config.xml***

```
<dataSource name="db1" type="JdbcDataSource" driver="com.mysql.jdbc.Driver" url="jdbc:mysql://host.com/db" user="uname" password="pwd" batchSize="-1"/>
```
-----
####<a name="configentityqueries"></a>entityQueries

Solr supports many entities in dataimport-config.xml that save to 1 record in the core
It is adviced to do as much using JOINS in your query, since performance is overall better, but exceptions can be made. 

entityQueries consists of key value pairs, where the key is the name/id of the entity and the value consists of it's configuration.

EntityQuery configs have these properties:

- **dataSource** : the key of the dataSource defined in [dataSources](#configdatasources). One dataSource can have multiple entities, but an entity only has 1 dataSource
- **transformers** : List of used [Solr transformers ](https://wiki.apache.org/solr/DataImportHandler#Transformer)
- **tables** : List of custom sets of from / join strings. The reason is that in all the queries a lot of the same table (groups) are queries, this way you can combine them any way you like, by refencing the tablegroup label instead of repeating FROM's and JOINS over and over
	- So the tables property has an lis of key-value-pairs, where the key is used as identifier and the value is a list of strings, containing FROM & JOINS
- **queries** : The entity has some (optional) [specific queries](https://wiki.apache.org/solr/DataImportHandler#Schema_for_the_data_config) that can be used accoring to the [Solr Documentation](https://wiki.apache.org/solr/DataImportHandler#Schema_for_the_data_config). These are the (optional) properties of the queries object. Each defining which tables, filters and optionally fields should be queried. Like: *query*, *deltaQuery*, *parentDeltaQuery*, *deletedPkQuery*, *deltaImportQuery*
	- **filter** : contains a string with the WHERE clause (optionaly also grouping/having) for this specific query. Use [placeholders](https://wiki.apache.org/solr/DataImportHandler#Using_delta-import_command) where nessecary
	- **tables**: contains a list of the id's of the applicable tablegroups use
 	- **fields**: (optional). Ordinary selects all fields applicable for this entity (dataSourceEntity property in [fields](#configfields)). Defining this property with a list of the fieldNames, it only queries those.
- **parentEntity** : Optionally this propertie enables entity nesting [see examples](https://wiki.apache.org/solr/DataImportHandler#Full_Import_Example)
- **pk**: If a parentEntity is defined, this forces the PrimaryKey 



*** <= config.json***

```
entityQueries: {
	mainentity: {
		dataSource: "db1",
		transformers: [
			"RegexTransformer"
			],
		tables: {
			main: [
				"FROM `maintable` t1"
			],
			all: [
				"LEFT JOIN `subtable` subt_land ON t1.`land`=subt_land.`code`",
				"LEFT JOIN `subtable` subt_provincie ON t1.`provincie`=subt_provincie.`code`",
				"LEFT JOIN `subtable` subt_moeder ON t1.`scode`=subt_moeder.`code`",
				"LEFT JOIN `subtable` subt_scode ON t1.`scode`=subt_scode.`code`",
				"LEFT JOIN `subtable` subt_bcode ON t1.`bcode`=subt_bcode.`code`"
			]
		},
		queries: {
			query: {
				filter: "WHERE t1.`status` > 0 GROUP BY t1.`klantnr`",
				tables: [
					"main",
					"all"
				]
			},
			deltaImportQuery: {
				filter: "WHERE t1.`klantnr`='${dih.delta.id}' GROUP BY t1.`klantnr`",
				tables: [
					"main",
					"all"
				]
			},
			deltaQuery: {
				filter: "WHERE t1.`updatedon` > '${dih.last_index_time}'",
				tables: [
					"main"
				],
				fields: [
					"id"
				]
			}
		}
	},
	subentity: {
		dataSource: "db2",
		parentEntity: "mainentity",
		pk: "klantnr",
		tables: {
			main: [
				"FROM `t1_surrounding` t1s"
			]
		},
		queries: {
			query: {
				filter: "WHERE t1s.`klantnr`= '${mainentity.id}'",
				tables: [
					"main"	
				]
			}
		}
	}
	...
```

*** => dataimport-config.xml***

```
  <document>
    <entity name="mainentity" dataSource="db1" transformer="RegexTransformer" pk="id" query="SELECT 	
    	t1.`klantnr` as `id`,
    	t1.`status` as `status`,
    	...
    	FROM `maintable` t1 
    	LEFT JOIN `subtable` subt_land ON t1.`land`=subt_land.`code`
    	...
		WHERE t1.`status` &gt; 0 GROUP BY t1.`klantnr`;" 
	deltaImportQuery="SELECT 
		t1.`klantnr` as `id`,
    	t1.`status` as `status`,
    	...
    	FROM `maintable` t1 
    	LEFT JOIN `subtable` subt_land ON t1.`land`=subt_land.`code`
    	...
		WHERE t1.`klantnr`='${dih.delta.id}' GROUP BY t1.`klantnr`;
	" deltaQuery="SELECT 
		t1.`klantnr` as `id` 
		FROM `maintable` t1 
		WHERE t1.`updatedon` &gt; '${dih.last_index_time}';">
		<field ... />
		<field ... />
		...
		<entity name="subentity" dataSource="db2" transformer="RegexTransformer" pk="klantnr" query="SELECT...
	
												
```
-----
####<a name="configsearchoptions"></a>searchOptions
This section defines global search options and the possibel search actions to be perfomed by the API.

Each property of searchOptions is a key-value-pair, where the key is the identifier for the search action to be performed/handled by the api.

Every action has these attributes

- **type**: Only handled values are edismax/dismax and *null* (standard parser) [documentation](https://cwiki.apache.org/confluence/display/solr/Query+Syntax+and+Parsing)
- **facets**: true/false whether facets are enabled for this query
- **options**: a array of key-value-pairs, that are passed to the select query (url parameter) [see](https://cwiki.apache.org/confluence/display/solr/The+Extended+DisMax+Query+Parser)
- **minimumMatch**: [see documentation](https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser)
- **queryPhraseSlop**: [see documentation](https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser)
- **phraseSlop**: [see documentation](https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser)
- **phraseBiGramSlop**: [see documentation](https://cwiki.apache.org/confluence/display/solr/The+Extended+DisMax+Query+Parser)
- **phraseTriGramSlop**: [see documentation](https://cwiki.apache.org/confluence/display/solr/The+Extended+DisMax+Query+Parser)
- **boostQuery**: [see documentation](https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser)
- **boostFunctions**: [see documentation](https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser)
- **boostFunctionsMult**: [see documentation](https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser)
- **tie**: [see documentation](https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser)
- **userFields**: [see documentation](https://cwiki.apache.org/confluence/display/solr/The+Extended+DisMax+Query+Parser)

*** <= config.json***

```
searchOptions: {
	search: {
		type: "EDisMax",
		facets: true,
		options: {
			lowercaseOperators: true,
			stopwords: true,
			indent: true,
			stats: true
		},
		minimumMatch: "2<-1 5<80%",
		queryPhraseSlop: 2,
		phraseSlop: 2,
		phraseBiGramSlop: 2,
		phraseTriGramSlop: 2
},
```
*** => apiconfig.json***

```
 "search": {
    "type": "EDisMax",
    "facets": true,
    "options": {
      "lowercaseOperators": true,
      "stopwords": true,
      "indent": true,
      "stats": true,
      "stats.field": [
        "latitude",
        "longitude"
      ]
    },
    "minimumMatch": "2<-1 5<80%",
    "queryPhraseSlop": 2,
    "phraseSlop": 2,
    "phraseBiGramSlop": 2,
    "phraseTriGramSlop": 2,
    ...
```

-----
####<a name="configdependencyvariables"></a>dependencyVariables
The dependency variables are a construct to create many similar fields without copy/pasting
The idea  is that a single field can duplicates be used to store for example different translations.

Every dependency variable is crossed with each other to create unique combinations so 3 dependencies with resp. 5, 8 and 3 iterators will generate 120 base field (multplied by number of subtypes) on fields dependant on all 3 dependencies.

In theory one field can reuse the same dependency, to create cross products of a single dependency

```
dependencyVariables: {
	language_codes: [
		"en",
		"nl",
		"de",
		"fr",
		"es",
		"it",
		"pl"
	],
	"colors": {
		"red",
		"blue",
		"green"
	}
},
```


-----



