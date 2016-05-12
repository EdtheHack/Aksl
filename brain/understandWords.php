<?php

$GLOBALS['order'] = "";
$GLOBALS['orders'] = [];

/*
 * Find noun first, then a verb, then a noun, then a verb
 * It is recursive in order to find sentences nested within nested sentences etc.
 * This puts the sentence through the understandSentence function which turns words into the tree
 */

function findNestedSentences($wordsDetailed) {
    $nounVerbCount = 0;
    $foundNoun = false;
    $foundSentence = false;
    $sentenceEnd = 0;

    //echo "NESTED SENTENCE FINDER";
    //print_r($wordsDetailed);

    $nestedSentences = new Node("ROOT", []);

    for ($i = 0; $i < count($wordsDetailed); $i++) {  //loop through all words
        echo "<br> COUNT = " . $i;
        if ($foundNoun == true && ($wordsDetailed[$i]['type'] == 'verb' || $wordsDetailed[$i]['type'] == 'adverb' || $wordsDetailed[$i]['type'] == 'auxiliary')) {
            $nounVerbCount++;    //increase count of noun/verb combinations
            $foundNoun = false;  //set this back to false so we need to find another noun/verb combination again
        }
        if ($wordsDetailed[$i]['type'] == 'noun' || $wordsDetailed[$i]['type'] == 'pronoun') {  //find a noun first
            $foundNoun = true;
            //echo "<br>" . $wordsDetailed[$i]['type'] . " - ". $wordsDetailed[$i]['word'] . "<br>";
        }

        if ($nounVerbCount > 1) {  //if noun/verb count is greater than 1
            $nounVerbCount = 1;
            $sentenceStart = findSentenceStart($i, $wordsDetailed);

            //$split = array_slice($wordsDetailed, $sentenceEnd, $sentenceStart - $sentenceEnd - 1);
            $split = array_slice($wordsDetailed, $sentenceEnd, $sentenceStart - $sentenceEnd);  //remove the first part of the array to separate the nested sentence

            $sentenceEnd = findSentenceEnd($i, $wordsDetailed);
            $i = $sentenceEnd - 1;

            //$SBAR = array("node" => "SBAR", $wordsDetailed[$sentenceStart - 1] , array_slice($wordsDetailed, $sentenceStart, $sentenceEnd - $sentenceStart));

            $nested = array_slice($wordsDetailed, $sentenceStart, $sentenceEnd - $sentenceStart);  //split the nested sentence from

            //echo "END :". $sentenceEnd . " - START:" . $sentenceStart;
            //$SBAR[1] = findNestedSentences($SBAR[1]);
            $nested = findNestedSentences($nested);  //recursive check for nested sentences inside the sentence we just found

            $splitAndNest = array_merge($split, array($nested));

            $splitAndNest = understandSentence($splitAndNest);

            $nestedSentences->addChild($splitAndNest);

            $foundSentence = true;
        }
    }

    if ($foundSentence){  //if we find a sentence return the nested sentence.
        return $nestedSentences;
    } else {  //if we dont find a sentence just return the words in the tree format.
        return understandSentence($wordsDetailed);
    }
}

/*
 * Travel back through the words until you pass a noun and hit a verb
 * This signals where the nested sentence starts
 */

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


/*
 * Travel forward through the words until you hit a conjunction or the end of the words
 * This signals where the nested sentence ends
 */

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

/*
 * Displays the node in a BEAUTIFUL format
 */

function displayTree($root) {
    if(is_a($root, "Node")) {

        echo "<div class='nodeRow'>";

        echo "<p>".$root->getValue()."<p>";
        foreach($root->getChildren() as $child) {
            displayTree($child);
        }

        echo "</div>";

    } else {
        echo "<div class='nodeRow'>";
            echo "<p>".$root['word']."<p>";
        echo "</div>";
    }
}

/*
 * I cant remember what this does
 * I don't think its used any more
 */

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

/*
 * This gets the information about a word from the database and puts in into an array.
 * e.g.
 *
 * ([word] => do, [type] => 'noun')
 *
 * It will also decide what to do with unknown words eventually
 */

function findWordInformation($words, $db_con) {
    $wordsDetailed = [];
    foreach ($words as $word) {
        $row = checkIfExists($word,$db_con);
        if($row){
            array_push($wordsDetailed, $row[0]);
        } else {
            if(substr($word, -1) == "s"){
                if($row = checkIfExists(substr($word, 0, -1), $db_con)){
                    $row[0]['tags']= "plural";
                    array_push($wordsDetailed, $row[0]);
                } else {
                    array_push($wordsDetailed, array("type" => "unknown", "word" => $word, "meaning" => ""));
                }
            }else {
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
function checkIfExists($word, $db_con){
    $wordResults=[];
    $stmt = $db_con->prepare("SELECT * FROM english WHERE word = :value;");
    $stmt->bindParam(':value', $word);
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($wordResults, $row);
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
    return $wordResults;
}
function findPlural($word, $db_con){

}

/*
 * I cant remember what this does
 * I don't think its used any more
 */


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

/*
 * I cant remember what this does
 * I don't think its used any more
 */


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

/*
 * I cant remember what this does
 * I don't think its used any more
 */

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

/*
 * Returns an array type that contains the correct format for storage.
 */
function createLeafNode($nodeType, $word){
    return ['node' => $nodeType, 'wordDetailed' => $word];
}

/*
 * This function turns the words into a tree from the root of the tree.
 * It is primarily based around finding a combination of word types
 *
 * e.g.
 *
 * NOUN -> VERB   =   split into a nounphase then a verbphase
 * VERB -> NOUN   =   split into a verbphase then a nounphase
 */

function understandSentence($words){
    //echo "<br><br>WORDS : ";
    //print_r($words);

    $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "S(";
    $tree = new Node("S", []);
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

        if (is_a($word, 'Node')) {    //Check what the word is first.
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
        } else if ($word['type'] == 'auxiliary') {
            $foundAux = true;
        }

        if ($foundSentenceNode && $foundNoun) {     //Check a whole bunch of combinations and split into certain phases
            $tree->addChild(nounPhase(array_slice($words, $split, $i)));
            $tree->addChild($word);
        } else if ($foundVerb && $foundCon) {
            $tree->addChild(verbPhase(array_slice($words, $split, $i)));
            $tree->addChild(new Node("CON", [$word]));
            $foundNoun = false;
            $foundVerb = false;
            $foundCon = false;
            $split = $i + 1;
        } else if ($foundNoun && $foundCon) {
            $tree->addChild(nounPhase(array_slice($words, $split, $i)));
            $tree->addChild(new Node("CON", [$word]));
            $foundNoun = false;
            $foundVerb = false;
            $foundCon = false;
            $split = $i + 1;
        } else if ($foundVerb && $foundAux) {
            $tree->addChild(verbPhase(array_slice($words, $split, $i)));
            $tree->addChild(new Node("AUX", [$word]));
            $foundNoun = false;
            $foundVerb = false;
            $foundAux = false;
            $split = $i + 1;
        } else if ($foundNoun && $foundAux) {
            $tree->addChild(nounPhase(array_slice($words, $split, $i)));
            $tree->addChild(new Node("AUX", [$word]));
            $foundNoun = false;
            $foundVerb = false;
            $foundAux = false;
            $split = $i + 1;
        } else if ($foundNoun && ($word['type'] == 'verb' || $word['type'] == 'adverb' || $word['type'] == 'auxiliary')) {
            $tree->addChild(nounPhase(array_slice($words, $split, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . ")";
            $tree->addChild(verbPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "))";
            return $tree;
        } else if ($foundVerb && ($word['type'] == 'noun' || $word['type'] == 'pronoun' || $word['type'] == 'determiner' || $word['type'] == 'adjective')) {
            $tree->addChild(verbPhase(array_slice($words, $split, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . ")";
            $tree->addChild(nounPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "))";
            return $tree;
        } else if ($foundNoun && $i >= count($words) - 1) {
            $tree->addChild(nounPhase(array_slice($words, $split)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "))";
        } else if ($foundInterjection && $i >= count($words) - 1) {
            $tree->addChild(nounPhase(array_slice($words, $split)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "))";
        } else if ($foundVerb && $i >= count($words) - 1) {
            $tree->addChild(verbPhase(array_slice($words, $split)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "))";
        } else if ($foundSentenceNode) {
            $tree->addChild($word);
        } else if ($foundCon) {
            $tree->addChild(new Node("CON", [$word]));
            $foundNoun = false;
            $foundVerb = false;
            $foundCon = false;
            $split = $i + 1;
        } else if ($foundAux) {
            $tree->addChild(new Node("AUX", [$word]));
            $foundNoun = false;
            $foundVerb = false;
            $foundAux = false;
            $split = $i + 1;
        }
        $i++;
    }
    return $tree;
}

/*
 * This function turns the words into a tree from a nounphase.
 * It is primarily based around finding a combination of word types
 *
 * e.g.
 *
 * NOUN -> PREPOSITION   =   split into a prepositionphase
 *
 * Also it adds single nodes like adjectives to the nounphase
 *
 * e.g.
 *
 * ADJECTIVE   =   simply add an adjective to the phase
 * NOUN   =   simply add a noun to the phase
 * PRONOUN   =   simply add a pronoun to the phase
 * DETERMINER   =   simply add a determiner to the phase
 */


function nounPhase($words) {
    $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "NP(";
    $nounPhase = new Node("NP", []);
    $i = 0;
    $split=0;
    $foundDeterminer = false;
    $foundAdj = false;
    $foundNoun = false;
    $foundSentenceNode = false;

    foreach ($words as $word) {
        if (is_a($word, 'Node')) {
            echo "<br>---- NODE ----<br>";
            $nounPhase->addChild($word);
            $foundSentenceNode = true;
        } else if ($word['type'] == 'determiner') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(DET)";
            $nounPhase->addChild(new Node("DET", [$word]));
            $foundDeterminer = true;
        } else if ($word['type'] == 'interjection') {
            $nounPhase->addChild(new Node("INJ", [$word]));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(INJ)";
            $foundDeterminer = true;
        } else if ($word['type'] == 'adjective') {
            $nounPhase->addChild(new Node("ADJ", [$word]));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(ADJ)";
            $foundAdj = true;
        } else if ($word['type'] == 'noun') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(NN)";
            $nounPhase->addChild(new Node("NN", [$word]));
            $foundNoun = true;
        } else if ($word['type'] == 'auxiliary') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(AUX)";
            $nounPhase->addChild(new Node("AUX", [$word]));
            $foundNoun = true;
        } else if ($word['type'] == 'pronoun') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(NNPRO)";
            $nounPhase->addChild(new Node("NNPRO", [$word]));
            $foundNoun = true;
        } else if ($word['type'] == 'conjunction') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(CON)";
            $nounPhase->addChild(new Node("CON", [$word]));
        }

        if ($foundSentenceNode == false) {
            if ($foundNoun && ($word['type'] == 'preposition')) {
                $nounPhase->addChild(preposPhase(array_slice($words, $i)));
                $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . ")";
                return $nounPhase;
                //$split = $i;
            } else if ($foundNoun && ($word['type'] == 'verb' || $word['type'] == 'adverb' || $word['type'] == 'auxiliary')) {
                $nounPhase->addChild(verbPhase(array_slice($words, $i)));
                $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . ")";
                return $nounPhase;
                //$split = $i;
            }
        }
        $i++;
    }

    return $nounPhase;
}



/*
 * This function turns the words into a tree from a prepositionphase.
 */

function preposPhase($words) {
    $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(PP";
    $preposPhase = new Node("PP", []);
    $foundPrepos = false;
    $i = 0;

    foreach ($words as $word) {
        $i++;
        if ($word['type'] == 'preposition') {
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "(P)";
            $preposPhase->addChild(new Node("P", [$word]));
            $preposPhase->addChild(nounPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. ")";
            return $preposPhase;
        }
    }
    return $preposPhase;
}

/*
 * This function turns the words into a tree from a verbphase.
 * It is primarily based around finding a combination of word types
 *
 * e.g.
 *
 * VERB -> NOUN   =   add a verb to the verbphase then go into a nounphase
 * VERB -> VERB   =   add a verb to the verbphase then go into a verbphase
 * ADVERB -> VERB   =   add an adverb to the verbphase then go into a verbphase
 */

function verbPhase($words) {
    $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. "VP(";
    $verbPhase = new Node("VP", []);//$verbPhase = ['node' => "VP"];
    $foundVerb = false;
    $foundSentenceNode = false;
    $i = 0;

    foreach ($words as $word) {
        if (is_a($word, 'Node')) {
            echo "<br>---- NODE ----<br>";
            $verbPhase->addChild($word);
            $foundSentenceNode = true;
        } else if ($foundVerb && ($word['type'] == 'verb' || $word['type'] == 'adverb')) {
            $verbPhase->addChild(verbPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. ")";
            return $verbPhase;
        } else if ($foundVerb && ($word['type'] == 'noun' || $word['type'] == 'pronoun' || $word['type'] == 'determiner' || $word['type'] == 'adjective')) {
            $verbPhase->addChild(nounPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. ")";
            return $verbPhase;
        } else if ($foundVerb && ($word['type'] == 'preposition')) {
            $verbPhase->addChild(preposPhase(array_slice($words, $i)));
            $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr']. ")";
            return $verbPhase;
        }

        if ($foundSentenceNode == false) {
            if ($word['type'] == 'auxiliary') {
                $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "(AUX)";
                $verbPhase->addChild(new Node("AUX", [$word]));
                $foundVerb = true;
            } else if ($word['type'] == 'verb') {
                $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "(V)";
                $verbPhase->addChild(new Node("V", [$word]));
                $foundVerb = true;
            } else if ($word['type'] == 'adverb') {
                $GLOBALS['sentenceStr'] = $GLOBALS['sentenceStr'] . "(ADV)";
                $verbPhase->addChild(new Node("ADV", [$word]));
                $foundVerb = true;
            } else if ($word['type'] == 'conjunction') {
                $verbPhase->addChild(new Node("CON", [$word]));
            }
            $i++;
        }
    }

    return $verbPhase;
}

/*
 * Loop through the tree and read information...
 * somehow
 */

function extractInformation($sentence) {
    $info = "";
    $i = 0;

    //echo "<br>SENTENCE:";
    //print_r($sentence);

    if ($sentence['node'] == 'NP') {
        echo "<br><br>NOUN PHASE: " . $info;
        createObjectSet($sentence);
    }
    if ($sentence['node'] == 'S') {
        echo "<br>INTO SENTENCE: " . $info;
    }

    if (isset($sentence['wordDetailed'])) {
        //echo $sentence['word'];
        //echo "<br>";
        $info = $info . $sentence['wordDetailed']['word'];
        echo "<br>INFO: " . $info;
        return  $sentence['wordDetailed']['word'];
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

/*
 * Puts a nounphase together then tries to add the nounphase to the database
 */

function createObjectSet($sentence) {
    $nounPhaseWords = [];

    foreach ($sentence as $word) {
        if (isset($word['wordDetailed'])) {
            array_push($nounPhaseWords, $word['wordDetailed']);
        }
    }

    addSet($nounPhaseWords);
}

function addSet($nounPhaseWords) {
    $db_con = BrainDB::getConnection();
    $multiple = false;
    $results = [];
    $where = "";

    foreach ($nounPhaseWords as $nounPhaseWord) {
        if ($multiple == true) {
            $where = $where . "OR ";
        }
        $where = $where . "word_id = '" . $nounPhaseWord['word_id'] . "' ";
        $multiple = true;
    }

    if (count($nounPhaseWords) > 1) {
        $stmt = $db_con->prepare("SELECT os1.set_id FROM object_sets AS os1 WHERE
        (SELECT COUNT(*)FROM object_sets AS os2 WHERE os1.set_id=os2.set_id AND ($where)) = ". count($nounPhaseWords)." AND ($where) LIMIT 1");
    } else {
        $stmt = $db_con->prepare("SELECT os1.set_id FROM object_sets AS os1 WHERE
        (SELECT COUNT(*)FROM object_sets AS os2 WHERE os1.set_id=os2.set_id) = ". count($nounPhaseWords)." AND ($where)");
    }

   // echo "<br><br>QUERY = " . $where;

    if ($stmt->execute()) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($results, $row);
        }
        echo "<br>";
        print_r($results);
        echo "<br>";
    } else {
        echo "ERROR QUERYING BRAIN #braindamage";
    }
    /*
    SELECT os1.set_id
    FROM object_sets AS os1
    WHERE (SELECT COUNT(*)
       FROM object_sets AS os2
       WHERE os1.set_id=os2.set_id)>1
     */
}