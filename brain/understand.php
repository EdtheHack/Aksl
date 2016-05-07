<?php

include "brain.php";

$feeling = ["happy" => 0, "sad" => 0, "angry" => 0,];
$words = explode(" " ,$_POST['response']);
$sentenceWords = [];
$sentenceStr = [];

$howDoIFeelAboutThis = 0;

$i = 0;

foreach ($words as $word) {
    $wordTypes = [];
    $wordMeanings = [];
    $wordThoughts = getWord($word);
    //print_r($wordThoughts);
    //echo "<br>";

    if (empty($wordThoughts)) {
        echo "- I dont know that word - <br>";
    } else {

        foreach ($wordThoughts as $words) {
            array_push($wordTypes, $words['wordType']);
        }
        array_push($sentenceWords, array("meanings" => $words['meaning'], "wordType" => $wordTypes, "word" => $word));

        foreach ($wordThoughts as $words) {
            if (isset($words['feel'])) {
                $howDoIFeelAboutThis += $words['feel'];
            }
        }
    }
    $i++;
}

//print_r( $sentenceWords);

reply($sentenceWords, $howDoIFeelAboutThis, $nouns, $determiners, $adverbs, $verbs, $interjections, $adjectives, $auxiliaries, $prepositions);