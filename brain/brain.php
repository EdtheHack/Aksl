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

//$sentence = understandSentence($nestedSentences);  //can put nested sentences in here


echo "<br>";
echo "<br> SENTENCE: ";
print_r($nestedSentences);
echo "<br>";
echo "<br>";

//$objects = getObjects($wordsDetailed);
//$context = getContext($wordsDetailed);
//echo "<br><br>" . $context . "<br><br>";
//$meanings = findMeaning($wordsDetailed);

//$order = notRecursiveThing($wordsDetailed);
//echo "<br>";
//echo "<br>";
//print_r($order);
//echo "<br>";
//echo "<br>";

//$convId = gen_id('conversations','conversation_id', $db_con);
//memoriseSentenceStructure($sentence, $db_con, $_POST["text"], $convId);

extractInformation($nestedSentences);
//$reply = thinkOfResponse($order, $db_con);
//echo $reply;

//print_r($sentence);

?>