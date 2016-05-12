<?php

/*
 * DATABASE CONNECTION
 */
require_once 'connect.php';
BrainDB::init();
$db_con = BrainDB::getConnection();

/*
 * INCLUDE APPROPRIATE FILES
 */
include_once "brain/understandWords.php";
include_once "brain/functions.php";
include_once "Tree/node.php";

$node0 = new Node("S", []);
$node1 = new Node("NP", []);
$node2 = new Node("VP", []);
$node3 = new Node("N", []);

$node0->addChild($node1);
$node0->addChild($node2);
$node0->removeChild($node2);

//$node2->addChild($node3);

print_r($node0);

/*
 * SET SOME GLOBAL VARIABLES
 *  Generally just makes like easier with recursive functions
 */
$GLOBALS['sentenceStr'] = '';
$GLOBALS['sentenceRegex'] = '';

/*
 * START PROCESSING WORDS
 */

$words = explode(" " ,$_POST["text"]);
$wordsDetailed = [];

$wordsDetailed = findWordInformation($words, $db_con);
$nestedSentences = findNestedSentences($wordsDetailed);

echo "<br>";
echo "<br> SENTENCE: ";
print_r($nestedSentences);
echo "<br>";
echo "<br>";
displayTree($nestedSentences);
echo "<br>";
echo "<br>";

//extractInformation($nestedSentences);

?>