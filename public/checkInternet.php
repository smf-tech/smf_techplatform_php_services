 <p id="checkOnline"></p>
<script>
  setInterval(function(){ 
 function isOnline(no,yes){
    var xhr = XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHttp');
    xhr.onload = function(){
        if(yes instanceof Function){
            yes();
        }
    }
    xhr.onerror = function(){
        if(no instanceof Function){
            no();
        }
    }
    xhr.open("GET","help.php",true);
    xhr.send();
}

isOnline(
    function(){
        console.log("Sorry, we currently do not have Internet access.");
    },
    function(){
        console.log("Succesfully connected!");
    }
);
  }, 3000);
</script> 