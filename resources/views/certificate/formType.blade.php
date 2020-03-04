
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
@-webkit-keyframes spin {
    0%   {
        -webkit-transform: rotate(0deg);
        -ms-transform: rotate(0deg);
        transform: rotate(0deg);
    }
    100% {
        -webkit-transform: rotate(360deg);
        -ms-transform: rotate(360deg);
        transform: rotate(360deg);
    }
}
@keyframes spin {
    0%   {
        -webkit-transform: rotate(0deg);
        -ms-transform: rotate(0deg);
        transform: rotate(0deg);
    }
    100% {
        -webkit-transform: rotate(360deg);
        -ms-transform: rotate(360deg);
        transform: rotate(360deg);
    }
}
</style>
<script type="text/javascript">
    function validate(){

    if( document.myForm.teacerCode.value == "" || isNaN( document.myForm.teacerCode.value ) ||
            document.myForm.teacerCode.value.length != 7 ) {
            
            alert( "Please enter 7 digit code." );
            document.myForm.teacerCode.focus() ;
            return false;
        } else if(document.myForm.trainingDays.value == "" || isNaN( document.myForm.trainingDays.value ))
        {
            document.myForm.trainingDays.focus() ;
            alert( "Please select days");
            return false;


        } 


        else{   

     $("#buttons").hide();
     $("#preloader").show();
     //$("#teacerCode").val("");
     setTimeout(function(){  $("#buttons").show();
     $("#preloader").hide();$("#teacerCode").val(""); $("#trainingDays").val(""); }, 15000);
    }
}
$(document).ready(function(){ 
       $("#preloader").hide();         
         }); 
</script>
</head>
<body>
<div class="container">  
<div class="row">
    <div class="logo">
        <div class="col-sm-6 col-md-6 smfLogo">
            <img src="http://13.235.105.204/images/SMFLogo.png" >
        </div>
        <div class="col-sm-6 col-md-6 mvLogo">
            <img src="http://13.235.105.204/images/MulyavardhanLogo.png" >
        </div>
    </div>    
</div>
</div>    
<div class="container">
    <div id="preloader">
  <div id="loader"></div>
</div>
    <div class="row justify-content-center">
        <div class="col-sm-12 certificateBox">
            
            @if(isset($errorMessage) && $errorMessage)
                <div class="panel panel-default">
                        <h3>{{$errorMessage}}</h3>
                </div>    
            @endif
            <div class="panel panel-default">

                <div class="panel-body">
                   
                        <h2><strong>Download Certificate for 
                         <?php 

                            if (strpos($type, '-') !== false) 
                             {   
                                $typeNew =  explode('-',$type);

                                 echo $typeNew[0];
                                
                            }else{

                                echo $type;
                            }

                           ?>                           
                        

                    </strong></h2>
                    <form action="http://13.235.105.204/api/downloadCertificate" method="post" name="myForm" onsubmit = "return(validate());">
                          
                        <legend></legend>

                        	<div class="form-group">
                                 <label for="name">Teacher Code (Staff ID)</label>
                                 <input type="number"  name="teacerCode" id="teacerCode" placeholder="code" class="form-control" required  />
                             </div>
                             <div>
                                
                                <input type="hidden" name="certificateType"  value="<?php 

                                    if (strpos($type, '-') !== false) 
                                     {   
                                        $typeNew =  explode('-',$type);

                                        echo $typeNew[0]; 
                                    }else{

                                        echo $type;
                                    }

                                   ?>"> 
                                   <?php

                                       if(isset($typeNew[1]) && $typeNew[1]!='')
                                       {
                                        echo '<span style="color:red;">'.str_replace('_', " ", $typeNew[1]).'</span>' ;
                                        } 
                                    ?>  
                             </div>

                             <?php 

                                    if (strpos($type, '-') !== false) 
                                     {   
                                        $typeNew =  explode('-',$type);

                                        $type = $typeNew[0]; 
                                    }else{

                                        $type;
                                    }

                                   ?>
                             @if($type == 'Shikshak')      
                             <div class="form-group">
                                 <label for="trainingDays">Training Days</label>
                                
                                 <select class="form-control" name="trainingDays" id="trainingDays">
                                     <option value="">---------Select Days--------</option>
                                     <option value="3">3</option>
                                     <option value="4">4</option>
                                     
                                 </select>    
                                
                             </div>
                             @endif
                            
                          
                            <br/>
                            <div id="levelContainer"  class="form-group">
                                   
                            </div>
                            <input type="submit" onClick="" id="buttons" class="btn btn-success btn-lg"/>
                         </form>                        
                </div>
            </div>
        </div>
    </div>
</div>




</body>
 
</html>
