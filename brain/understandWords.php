<?php

$GLOBALS['order'] = "";
$GLOBALS['orders'] = [];

function findNestedSentences($wordsDetailed) {  //find noun first, then a verb, then a noun, then a verb
    $nounVerbCount = 0;
    $foundNoun = false;
    $foundSentence = false;
    $sentenceEnd = 0;

    //echo "NESTED SENTENCE FINDER";
    //print_r($wordsDetailed);

    $nestedSentences = [];

    for ($i = 0; $i < count($wordsDetailed); $i++) {
        echo "<br> COUNT = " . $i;
        if ($foundNoun == true && ($wordsDetailed[$i]['type'] == 'verb' || $wordsDetailed[$i]['type'] == 'adverb' || $wordsDetailed[$i]['type'] == 'auxiliary')) {
            $nounVerbCount++;
            $foundNoun = false;
        }
        if ($wordsDetailed[$i]['type'] == 'noun' || $wordsDetailed[$i]['type'] == 'pronoun') {
            $foundNoun = true;
            //echo "<br>" . $wordsDetailed[$i]['type'] . " - ". $wordsDetailed[$i]['word'] . "<br>";
        }

        if ($nounVerbCount > 1) {
            $nounVerbCount = 1;
            $sentenceStart = findSentenceStart($i, $wordsDetailed);

            //$split = array_slice($wordsDetailed, $sentenceEnd, $sentenceStart - $sentenceEnd - 1);
            $split = array_slice($wordsDetailed, $sentenceEnd, $sentenceStart - $sentenceEnd);
            echo "<br>Split";
            print_r($split);

            $sentenceEnd = findSentenceEnd($i, $wordsDetailed);
            $i = $sentenceEnd - 1;

            //$SBAR = array("node" => "SBAR", $wordsDetailed[$sentenceStart - 1] , array_slice($wordsDetailed, $sentenceStart, $sentenceEnd - $sentenceStart));
            //echo "<br>SBAR";
            //print_r($SBAR);
            $nested = array_slice($wordsDetailed, $sentenceStart, $sentenceEnd - $sentenceStart);

            //echo "END :". $sentenceEnd . " - START:" . $sentenceStart;
            //$SBAR[1] = findNestedSentences($SBAR[1]);
            $nested = findNestedSentences($nested);
            //echo "<br>NESTED";
            //print_r($nested);

            $splitAndNest = array_merge($split, array($nested));

            //echo "<br>SPLIT AND NESTED";
            //print_r($splitAndNest);

            $splitAndNest = understandSentence($splitAndNest);

            //echo "<br> SPLIT AND NEST AFTER UNDERSTAND";
            //print_r($splitAndNest);


            $nestedSentences = array_merge($nestedSentences, $splitAndNest);

            echo "<br> ADDED SENTENCE";
            print_r($nestedSentences);

            $foundSentence = true;
        }
    }

    if ($foundSentence){
        return $nestedSentences;
    } else {
        return understandSentence($wordsDetailed);
    }
}

function findSentenceStart($i, $wordsDetailed) {
    $searching = true;
    $nounFound = false;

    while ($searching == true) {
        if ($nounFound == true && $wordsDetailed[$i]['type'] == 'verb' || $wordsDetailed[$i]['type'] == 'adverb' || $wordsDetailed[$i]['type'] == 'conjunction') {
            return ($i + 1);
        }
        if ($wordsDetailed[$i]['type'] == 'noun' || $wordsDetailed[$i]['type'] == 'pronoun') {
            $nounFound = true;
        }
        $i--;
    }
}

function findSentenceEnd($i, $wordsDetailed) {
    $searching = true;

    while ($searching == true) {
        if ($i >= count($wordsDetailed)) {
            return $i;
        }

        if ($wordsDetailed[$i]['type'] == 'conjunction') {
            return $i;
        }
        $i++;
    }
}

function getContext($words) {
    $i = 0;
    $context = " ";
    $nounFound = false;

    foreach ($words as $word) {
        $i++;
        print_r($word);
        echo "<br>";

        if ($word['type'] == 'noun' || $word['type'] == 'pronoun' || $word['type'] == 'adjective') {
            $nounFound = true;
        }

        if ($nounFound == true && ($word['type'] != 'verb' && $word['type'] != 'adverb' && $word['type'] != 'auxiliary')) {
            $context = $context . $word['word'] . " ";
        }
        if ($nounFound == true && ($word['type'] == 'verb' || $word['type'] == 'adverb' || $word['type'] == 'auxiliary' || $i == count($words))) {
            $context = trim($context, " ");
            return $context;
            //$context = str_ireplace(" you ", " I ", $context, $count);
            //if ($count > 0) {
            //    return trim($context, " ");
            //} else {
            //    $context = str_ireplace(" I ", " you ", $context, $count);
            //    return trim($context, " ");
            //}
        }
    }

    return "you";  //does not maintain contexts
}

function findWordInformation($words, $db_con) {
    $wordsDetailed = [];
    foreach ($words as $word) {
        $stmt = $db_con->prepare("SELECT * FROM english WHERE word = :value;");
        $stmt->bindParam(':value', $word);
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push($wordsDetailed, $row);
                }
            } else {
                array_push($wordsDetailed, array("type" => "unknown", "word" => $word, "meaning" => ""));
            }
        }
    }

    $i = 0;
    foreach ($wordsDetailed as $word) {
        if ($word['type'] == 'unknown') {
            $wordsDetailed[$i]["type"] = findUnknownWord($wordsDetailed, $i);
        }
        $i++;
    }

    //print_r($wordsDetailed);
    //echo "<br>";

    return $wordsDetailed;
}


function thinkOfResponse($orders, $db_con) {
    for ($i = count($orders)- 1; $i >= 0; $i--) {
        foreach ($orders[$i] as $word){
            print_r($word);
            echo "<br>";
        }

    }
    echo "<br>";
    echo "<br>";
}

function memoriseSentenceStructure($sentence, $db_con, $words, $convId) {
    $stmt = $db_con->prepare("SELECT * FROM sentencestructures WHERE ss = :value;");
    $stmt->bindParam(':value', $GLOBALS['sentenceStr']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $stmt = $db_con->prepare("UPDATE sentencestructures SET correctness = correctness + 1 WHERE ss = :value");
        $stmt->bindParam(':value', $GLOBALS['sentenceStr']);
        if ($stmt->execute()) {
            echo "reinforced sentence structure<br>";
        } else {
            echo "error<br>";
        }
    } else {
        $stmt = $db_con->prepare("INSERT INTO sentencestructures VALUES ('', :value, :valuess, 0)");
        $stmt->bindParam(':value', json_encode($sentence));
        $stmt->bindParam(':valuess', $GLOBALS['sentenceStr']);
        if ($stmt->execute()) {
            echo "sentence structure found <br>";
        }
    }

   /* $stmt = $db_con->prepare("INSERT INTO conversations VALUES ($convId , :value, '', 0)");
    $stmt->bindParam(':value', $words);
    if ($stmt->execute()) {
        echo "added a response";
    }*/
}

function findUnknownWord($words, $position) {
    $wordsBeforeUnk = [];
    $wordsAfterUnk = [];

    for ($i = $position - 2; $i < $position; $i++){
        if ($i > 0){
            array_push($wordsBeforeUnk, $words[$i]["type"]);
        } else {
            array_push($wordsBeforeUnk, NULL);
        }
    }

    for ($i = $position + 1; $i < $position + 3; $i++){
        if ($i < count($words)){
            array_push($wordsAfterUnk, $words[$i]["type"]);
        } else {
            array_push($wordsAfterUnk, NULL);
        }
    }

    echo "UNKNOWN <br>";
    print_r($wordsBeforeUnk);
    echo "<br>";
    print_r($wordsAfterUnk);
    echo "<br>";
    return "unknownNEEDSWORK";
}

function understandSentence($words){
    //echo "<br><br>WORDS : ";
    //print_r($words);

    $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "S(";
    $tree = ['node' => "S"];
    $i = 0;
    $split=0;

    $foundNoun = false;
    $foundVerb = false;
    $foundInterjection = false;
    $foundAux = false;
    $foundCon = false;
    $foundSentenceNode = false;

    foreach ($words as $word) {
        //if ($word['type'] == 'unknown') {
        //    array_push($tree, array(['UNKN' => "UNKNOWN"]));
        //    $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(UNKN)";
        //}

        echo "<br> WORDS: ";
        print_r($word);
        if (isset($word['node']) && ($word['node'] == 'S' || $word['node'] == 'SBAR')) {
            echo "<br>---- NODE ----<br>";
            $foundSentenceNode = true;
        } else if ($word['type'] == 'noun' || $word['type'] == 'pronoun' || $word['type'] == 'determiner' || $word['type'] == 'adjective' ) {
            $foundNoun = true;
        } else if ($word['type'] == 'verb' || $word['type'] == 'adverb') {
            $foundVerb = true;
        } else if ($word['type'] == 'interjection') {
            $foundInterjection = true;
        } else if ($word['type'] == 'conjunction') {
            $foundCon = true;
            echo "SFEOIUN IUFHIOFHI";
        } else if ($word['type'] == 'auxiliary') {
            $foundAux = true;
        }

        if ($foundSentenceNode && $foundNoun) {
            array_push($tree, nounPhase(array_slice($words, $split, $i)));
            array_push($tree, $word);
        } else if ($foundVerb && $foundCon) {
            array_push($tree, verbPhase(array_slice($words, $split, $i)));
            array_push($tree, ['node' => 'CON', "word" => $word['word'], "meaning" => $word['meaning']]);
            $foundNoun = false;
            $foundVerb = false;
            $foundCon = false;
            $split = $i + 1;
        } else if ($foundNoun && $foundCon) {
            array_push($tree, nounPhase(array_slice($words, $split, $i)));
            array_push($tree, ['node' => 'CON', "word" => $word['word'], "meaning" => $word['meaning']]);
            $foundNoun = false;
            $foundVerb = false;
            $foundCon = false;
            $split = $i + 1;
        } else if ($foundVerb && $foundAux) {
            array_push($tree, verbPhase(array_slice($words, $split, $i)));
            array_push($tree, ['node' => 'AUX', "word" => $word['word'], "meaning" => $word['meaning']]);
            $foundNoun = false;
            $foundVerb = false;
            $foundAux = false;
            $split = $i + 1;
        } else if ($foundNoun && $foundAux) {
            array_push($tree, nounPhase(array_slice($words, $split, $i)));
            array_push($tree, ['node' => 'AUX', "word" => $word['word'], "meaning" => $word['meaning']]);
            $foundNoun = false;
            $foundVerb = false;
            $foundAux = false;
            $split = $i + 1;
        } else if ($foundNoun && ($word['type'] == 'verb' || $word['type'] == 'adverb' || $word['type'] == 'auxiliary')) {
            array_push($tree, nounPhase(array_slice($words, $split, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . ")";
            array_push($tree, verbPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "))";
            return $tree;
        } else if ($foundVerb && ($word['type'] == 'noun' || $word['type'] == 'pronoun' || $word['type'] == 'determiner' || $word['type'] == 'adjective')) {
            array_push($tree, verbPhase(array_slice($words, $split, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . ")";
            array_push($tree, nounPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "))";
            return $tree;
        } else if ($foundNoun && $i >= count($words) - 1) {
            array_push($tree, nounPhase(array_slice($words, $split)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "))";
        } else if ($foundInterjection && $i >= count($words) - 1) {
            array_push($tree, nounPhase(array_slice($words, $split)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "))";
        } else if ($foundVerb && $i >= count($words) - 1) {
            array_push($tree, verbPhase(array_slice($words, $split)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "))";
        } else if ($foundSentenceNode) {
            array_push($tree, $word);
        } else if ($foundCon) {
            array_push($tree, ['node' => 'CON', "word" => $word['word'], "meaning" => $word['meaning']]);
            $foundNoun = false;
            $foundVerb = false;
            $foundCon = false;
            $split = $i + 1;
        } else if ($foundAux) {
            array_push($tree, ['node' => 'AUX', "word" => $word['word'], "meaning" => $word['meaning']]);
            $foundNoun = false;
            $foundVerb = false;
            $foundAux = false;
            $split = $i + 1;
        }
        $i++;
    }
    return $tree;
}

function nounPhase($words) {
    $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "NP(";
    $nounPhase = ['node' => "NP"];
    $i = 0;
    $split=0;
    $foundDeterminer = false;
    $foundAdj = false;
    $foundNoun = false;
    $foundSentenceNode = false;

    foreach ($words as $word) {
        if (isset($word['node']) && ($word['node'] == 'S' || $word['node'] == 'SBAR')) {
            echo "<br>---- NODE ----<br>";
            array_push($nounPhase, $word);
            $foundSentenceNode = true;
        } else if ($word['type'] == 'determiner') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(DET)";
            array_push($nounPhase, ['node' => "DET", "word" => $word['word'], "meaning" => $word['meaning']]);
            $foundDeterminer = true;
        } else if ($word['type'] == 'interjection') {
            array_push($nounPhase, ['node' => "INJ", "word" => $word['word'], "meaning" => $word['meaning']]);
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(INJ)";
            $foundDeterminer = true;
        } else if ($word['type'] == 'adjective') {
            array_push($nounPhase, ['node' => 'ADJ', "word" => $word['word'], "meaning" => $word['meaning']]);
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(ADJ)";
            $foundAdj = true;
        } else if ($word['type'] == 'noun') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(NN)";
            array_push($nounPhase, ['node' => 'NN', 'word' => $word['word'], "meaning" => $word['meaning']]);
            $foundNoun = true;
        } else if ($word['type'] == 'auxiliary') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(AUX)";
            array_push($nounPhase, ['node' => 'AUX','word' => $word['word'], "meaning" => $word['meaning']]);
            $foundNoun = true;
        } else if ($word['type'] == 'pronoun') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(NNPRO)";
            array_push($nounPhase, ['node' => 'NNPRO', "word" => $word['word'], "meaning" => $word['meaning']]);
            $foundNoun = true;
        } else if ($word['type'] == 'conjunction') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(CON)";
            array_push($nounPhase, ['node' => 'CON', "word" => $word['word'], "meaning" => $word['meaning']]);
        }

        if ($foundSentenceNode == false) {
            if ($foundNoun && ($word['type'] == 'preposition')) {
                array_push($nounPhase, preposPhase(array_slice($words, $i)));
                $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . ")";
                return $nounPhase;
                //$split = $i;
            } else if ($foundNoun && ($word['type'] == 'verb' || $word['type'] == 'adverb' || $word['type'] == 'auxiliary')) {
                array_push($nounPhase, verbPhase(array_slice($words, $i)));
                $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . ")";
                return $nounPhase;
                //$split = $i;
            }
        }
        $i++;
    }

    return $nounPhase;
}

function preposPhase($words) {
    $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(PP";
    $preposPhase = ['node' => "PP"];
    $foundPrepos = false;
    $i = 0;

    foreach ($words as $word) {
        $i++;
        if ($word['type'] == 'preposition') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(P)";
            array_push($preposPhase, ['node' => 'P', "word" => $word['word'], "meaning" => $word['meaning']]);
            array_push($preposPhase, nounPhase(array_slice($words, $i, count($words))));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. ")";
            return $preposPhase;
        }
    }
    return $preposPhase;
}

function verbPhase($words) {
    $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "VP(";
    $verbPhase = ['node' => "VP"];
    $foundVerb = false;
    $foundSentenceNode = false;
    $i = 0;

    foreach ($words as $word) {
        if (isset($word['node']) && ($word['node'] == 'S' || $word['node'] == 'SBAR')) {
            echo "<br>---- NODE ----<br>";
            array_push($verbPhase, $word);
            $foundSentenceNode = true;
        } else if ($foundVerb && ($word['type'] == 'verb' || $word['type'] == 'adverb')) {
            array_push($verbPhase, verbPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. ")";
            return $verbPhase;
        } else if ($foundVerb && ($word['type'] == 'noun' || $word['type'] == 'pronoun' || $word['type'] == 'determiner' || $word['type'] == 'adjective')) {
            array_push($verbPhase, nounPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. ")";
            return $verbPhase;
        } else if ($foundVerb && ($word['type'] == 'preposition')) {
            array_push($verbPhase, preposPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. ")";
            return $verbPhase;
        }

        if ($foundSentenceNode == false) {
            if ($word['type'] == 'auxiliary') {
                $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "(AUX)";
                array_push($verbPhase, ['node' => 'AUX', "word" => $word['word'], "meaning" => $word['meaning']]);
                $foundVerb = true;
            } else if ($word['type'] == 'verb') {
                $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "(V)";
                array_push($verbPhase, ['node' => 'V', "word" => $word['word'], "meaning" => $word['meaning']]);
                $foundVerb = true;
            } else if ($word['type'] == 'adverb') {
                $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "(ADV)";
                array_push($verbPhase, ['node' => 'ADV', "word" => $word['word'], "meaning" => $word['meaning']]);
                $foundVerb = true;
            } else if ($word['type'] == 'conjunction') {
                array_push($verbPhase, ['node' => 'CON', "word" => $word['word'], "meaning" => $word['meaning']]);
            }
            $i++;
        }
    }

    return $verbPhase;
}

function extractInformation($sentence) {
    $info = "";
    $i = 0;

    //echo "<br>SENTENCE:";
    //print_r($sentence);

    if (isset($sentence['word'])) {
        //echo $sentence['word'];
        //echo "<br>";
        $info = $info . $sentence['word'];
        echo "<br>INFO: " . $info;
        return  $sentence['word'];
    } else {
        foreach ($sentence as $node) {
            //print_r($node);
            //echo "<br>";
            if (is_array($node)) {
                $info = $info . extractInformation($node);
            }
            $i++;
        }
    }

    if ($sentence['node'] == 'S') {
        echo "<br>FINAL INFO: " . $info;
    } else {
        //echo "<br>SOME INFO: " . $info;
        return $info;
    }
}

