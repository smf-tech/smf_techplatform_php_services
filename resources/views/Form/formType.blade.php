
<html>
 
<head>
 
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
<meta charset="utf-8">
<style type="text/css">
  .smfLogo{
   
    /*margin-left: 10%;*/
    margin-top: 3%;
    margin-bottom: 2%;
    float: left;
  }
  .mvLogo{
    
    /*margin-right: 10%;*/
    margin-top: 3%;
    margin-bottom: 2%;
    float: right;
    text-align: right;
  }
.form-control{
    height: 70px;
    width:50%;
    margin:0px !important;
}

select.form-control{
    height: 70px;
    width:20%;
    margin:0px !important;
}
  .certificateBox{

    margin: 0 auto !important;
    width: 100%
    padding: 20px;
  }
  h2{
    text-align: center;
    font-weight:700;
  }
  input::placeholder {
  color: #bbbbbb !important;
  font-size: 23px;
}
body{
    font-size: 30px;
}
.btn-lg{
    font-size: 25px;
}

#preloader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 999;
}
#loader {
    display: block;
    position: relative;
    left: 50%;
    top: 50%;
    width: 150px;
    height: 150px;
    margin: -75px 0 0 -75px;
    border-radius: 50%;
    border: 3px solid transparent;
    border-top-color: #9370DB;
    -webkit-animation: spin 2s linear infinite;
    animation: spin 2s linear infinite;
}
 
#loader:after {
    content: "";
    position: absolute;
    top: 15px;
    left: 15px;
    right: 15px;
    bottom: 15px;
    border-radius: 50%;
    border: 3px solid transparent;
    border-top-color: #FF00FF;
    -webkit-animation: spin 1.5s linear infinite;
    animation: spin 1.5s linear infinite;
}


</style>
<script type="text/javascript">

  window.addEventListener('online',updateStatus);
  window.addEventListener('offline',updateStatus);
  function updateStatus(event){
    if(navigator.online){
      console.log('Your internet connection is back');
    } else{
      console.log('You have lost your internet connection.');
    }

  }
  function getData(){
      alert($('#userId').val()+'--userRole--'+$('#userRole').val()+'--userName--'+$('#userName').val());
     if ($('#userId').val()) {
            var userId = $('#userId').val();
            localStorage.setItem("userId", $('#userId').val());
        }
        if ($('#userRole').val()) {
            var userRole = $('#userRole').val();

            //$('#userRole').val(localStorage["userRole"]);
            localStorage.setItem("userRole", $('#userRole').val());
        }
        if ($('#userName').val()) {
           var userName = $('#userName').val();
           // $('#userName').val(localStorage["userName"]);
            localStorage.setItem("userName", $('#userName').val());
        }

         var db = openDatabase('mydb', '1.0', 'Test DB', 2 * 1024 * 1024); 
         var msg; 
    
         db.transaction(function (tx) { 
            tx.executeSql('CREATE TABLE IF NOT EXISTS USERDATA (id, userName, Role)'); 
            tx.executeSql('INSERT INTO USERDATA (id, userName, Role) VALUES ("'+userId+'", "'+userName+'","'+userRole+'")'); 
            //tx.executeSql('INSERT INTO USERDETAILS (id, log) VALUES (7, "'+userRole+'")'); 
           // msg = '<p>Log message created and row inserted.</p>'; 
  });
}
  $( "#localStorageTest" ).submit(function () {

    alert('Test');

    // function init() {
    //     if (localStorage["userId"]) {
    //         $('#userId').val(localStorage["userId"]);
    //     }
    //     if (localStorage["userRole"]) {
    //         $('#userRole').val(localStorage["userRole"]);
    //     }
    //     if (localStorage["userName"]) {
    //         $('#userName').val(localStorage["userName"]);
    //     }
    // }
    init();
});

$('.stored').keyup(function () {
    localStorage[$(this).attr('name')] = $(this).val();
});

$('#localStorageTest').submit(function() {
    localStorage.clear();
});

$( "#other" ).click(function() {
  $( "#localStorageTest" ).submit();
});
</script>
</head>
<body>
<div class="container">  

</div>    
<div class="container">
 
    <div class="row justify-content-center">
        <div class="col-sm-12 certificateBox">
            
            @if(isset($errorMessage) && $errorMessage)
                <div class="panel panel-default">
                        <h3>{{$errorMessage}}</h3>
                </div>    
            @endif
            <div class="panel panel-default">
                <div id="result"></div>
                <div class="panel-body">
                   
                                       
                        
                    <!-- action="http://13.235.124.3:8090/api/saveForm" -->
                    </strong></h2>
                    <form id="localStorageTest" action="#" method="post" name="myForm">
                          
                        <legend></legend>
                             <div class="form-group">
                                 <label for="name">User Id</label>
                                 <input type="text"  name="userId" id="userId" placeholder="Id" class="form-control"/>
                             </div>
                             <div class="form-group">
                                 <label for="name">User Role</label>
                                 <input type="text"  name="userRole" id="userRole" placeholder="Role" class="form-control"/>
                             </div>  
                        	   <div class="form-group">
                                 <label for="name">User Name</label>
                                 <input type="text"  name="userName" id="userName" placeholder="User Name" class="form-control"/>
                             </div>
                           
                            <div id="levelContainer"  class="form-group">
                                   
                            </div>
                            <input type="button" onClick="getData()" class="btn btn-success btn-lg"/>
                         </form>                        
                </div>
            </div>
        </div>
    </div>
</div>

<div id="other">
  Trigger the handler
</div>


</body>
 
</html>
