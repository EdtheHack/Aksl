<?php

if (isset($_POST['text'])){
    include "brain/brain.php";
}

//if (isset($_POST['text'])){
    //include "brain/rateReply.php";
//}

?>
<html>
    <head>
        <title>Aksl</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js "></script>
        <script>
            function converse(){
/*
                var content = document.getElementById('inputBox').value;

                $.ajax({
                    type : "POST",
                    url : "brain/understandWords.php",
                    data:({text: content

                    }),
                    beforeSend : function() {
                        $(".post_submitting").show().html("<center><img src='images/loading.gif'/></center>");
                    },
                    success : function(data) {
                        $("#aksl").html(data);
                    }
                });*/
            }

            function rateResponse(text, convId, reply){
                 var rate = document.getElementById('rating').value;

                console.log(rate);

                 $.ajax({
                 type : "POST",
                 url : "brain/rateReply.php",
                 data:({text: text, convId: convId, reply: reply, rate: rate

                 }),
                 beforeSend : function() {
                 $(".post_submitting").show().html("<center><img src='images/loading.gif'/></center>");
                 },
                 success : function(data) {
                 $("#rate").html(data);
                 }
                 });
            }
        </script>
    </head>

    <body>
    <div id="aksl">

    </div>

    <form method="post" action="">
        <input type="text" id="inputBox" name="text">
        <input type="submit" id="inputButton" onclick="converse()">
    </form>

    <?php if (isset($convId)) { ?>
        Did this response make sense /10?
        <input type="text" id="rating" name="rate_response">
        <input type="hidden" id="inputBox" name="response" value="<?php echo $_POST['text']; ?>">
        <input type="hidden" id="inputBox" name="conv_id" value="<?php echo $convId; ?>">
        <input type="hidden" id="inputBox" name="reply" value="<?php echo $convId; ?>">
        <input type="submit" id="inputButton" onclick="rateResponse(<?php echo "'" .$_POST['text'] . "', '" . $convId . "','" . $convId . "'"; ?>)">
    <?php } ?>
    <div id="rate">

    </div>
    </body>
</html>


