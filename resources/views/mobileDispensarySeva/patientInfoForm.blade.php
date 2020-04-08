
<html>
 
<head>
 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <meta charset="utf-8">
   <style type="text/css">
    @font-face {
           
            font-family: 'Noto Sans', sans-serif !important;
            font-weight: normal;
            src: url('http://13.235.105.204/fonts/NotoSans-Regular.ttf') format('truetype');
        }
     
       

       body,html{padding:0; margin:0.0rem;height:100%;width:100%;}
        body{
           /* background: url('http://13.235.105.204/images/2019_MV_Certificates_11_12_2019_Final_Prerak.png');*/
            font-family: 'Noto Sans', sans-serif !important; 
            font-size: 2em !important;
            line-height: 2em !important; 
        }

        #flashmsg {
            color: 
        }

    /* .... Other css rules here ... */

</style>
<script type="text/javascript">
    
    function createCookie(cookieName,cookieValue,hoursToExpire)
        {
          var date = new Date();
          //date.setTime(date.getTime()+(daysToExpire*24*60*60*1000));
          date.setTime(date.getTime()+(hoursToExpire*60*60*1000));
          document.cookie = cookieName + "=" + cookieValue + "; expires=" + date.toGMTString();
        }
    function accessCookie(cookieName)
        {
          var name = cookieName + "=";
          var allCookieArray = document.cookie.split(';');
          for(var i=0; i<allCookieArray.length; i++)
          {
            var temp = allCookieArray[i].trim();
            if (temp.indexOf(name)==0)
            return temp.substring(name.length,temp.length);
          }
          return "";
        }
    function checkCookie()
        {
          var vanCode = accessCookie("vanCode");
          var currentVanCode = <?php echo json_encode($vanCode??''); ?>; //
          if(currentVanCode != '' && vanCode !=currentVanCode)
          {
            

            currentvanCodeArr = currentVanCode.split("_");
            console.log(currentvanCodeArr);
            document.getElementById('vanCode').value =currentvanCodeArr[0]+'('+currentvanCodeArr[1]+')';
           

            document.cookie = currentVanCode + '=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
                
                createCookie("vanCode", currentVanCode, 12);


          }else if( vanCode !='' )
          {
            
            vanCodeArr = vanCode.split("_");
            
            document.getElementById('vanCode').value =vanCodeArr[0]+'('+vanCodeArr[1]+')';

          }else if(vanCode == '' && currentVanCode == '') {
            
            document.getElementById('showMsg').innerHTML = 'Please fill Daily Vehicle Sheet.';
          }
         
         
        }

        function backButtonfunction()
        {
            window.location.href = "http://13.235.105.204/api/selectVan";
        }

        //$("#flashmsg").hide("slow");
        setTimeout(function() {
            $('#flashmsg').fadeOut('slow');
        }, 1000); 
</script>
</head>
<body onload="checkCookie()">
<div class="container">
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">

                <div class="panel-body">
                        <div id="showMsg" class="alert-warning">
        
                        </div>
                        <div id="flashmsg" class="alert-success">{{$msg ?? ""}}</div>
                        <div  class="form-group">
                            <input type="button" class="btn btn-success" value="Back" onclick="backButtonfunction()"/>
                        </div>
                        <h3>Patient Information Van</h3>
                    <form action="http://13.235.105.204/api/savePatientInfo " method="post">
                          
                        <legend></legend>

                            
                            <div class="form-group">
                                 <label for="patient_area">Van Code </label>
                                <input type="text" id="vanCode" name="vanCode" placeholder="vanCode" class="form-control"  readonly="readonly"  required = "required" value="" }} />
                                     
                                
                             </div>
                             <div class="form-group">
                                 <label for="patient_area">Area </label>
                                 <input type="read" name="patient_area" placeholder="area" class="form-control" required = "required" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Area')" oninput="setCustomValidity('')"/>
                                
                             </div>
                             <div class="form-group">
                                 <label for="name">Patient Name</label>
                                 <input type="text" name="patient_name" placeholder="patient name" class="form-control" required = "required" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Patient Name')" oninput="setCustomValidity('')"/>
                                
                             </div>

                             <div class="form-group">
                                 <label for="patient_phone">Mobile Number</label>
                                 <input type="text" name="patient_phone" placeholder="mobile number" class="form-control" required = "required" pattern= "[0-9]+" minlength="10" maxlength="10" oninvalid="this.setCustomValidity('Please Enter Valid Mobile Number')" oninput="setCustomValidity('')"/>
                                
                             </div>
                             <div class="form-group">
                                 <label for="age">Age</label>
                                 <input type="text" name="patient_age" placeholder="age"  pattern= "[0-9]+" minlength="1" maxlength="3"class="form-control" required = "required" oninvalid="this.setCustomValidity('Please Enter Valid Age')" oninput="setCustomValidity('')"/>
                                
                             </div>

                             <div class="form-group">
                                 <label for="paitent_gender">Gender</label>
                                

                                 <select class="form-control" name="patient_gender" id="patient_gender" required = "required"oninvalid="this.setCustomValidity('Please Select Your Gender')" oninput="setCustomValidity('')">
                                     <option value="">---------Choose Gender---------</option>
                                     <option value="Male">Male</option>
                                     <option value="Female">Female</option>
                                     <option value="Other">Other</option>
                                    
                                 </select>    
                                
                             </div>

                             <div class="form-group">
                                 <label for="self-isolate">Patient asked to self-isolate?</label>
                                 <select class="form-control" name="patient_self_isolate" id="patient_self_isolate" required = "required" oninvalid="this.setCustomValidity('Please Select Your Option')" oninput="setCustomValidity('')">
                                     <option value="">---------Choose ---------</option>
                                     <option value="Yes">Yes</option>
                                     <option value="No">No</option>
                                     
                                 </select>
                                
                             </div>

                             <div class="form-group">
                                 <label for="patient_corona_suspect">Corona Suspect</label>
                                 <select class="form-control" name="patient_corona_suspect" id="patient_corona_suspect" required = "required" oninvalid="this.setCustomValidity('Please Select Your Option')" oninput="setCustomValidity('')">
                                     <option value="">---------Choose ---------</option>
                                     <option value="Yes">Yes</option>
                                     <option value="No">No</option>
                                     
                                 </select>
                                
                             </div>
                             

                             
                          
                            <br/>
                            <div id="levelContainer"  class="form-group">
                                   
                            </div>
                            <input type="submit" class="btn btn-success"/>
                         </form>                        
                </div>
            </div>
        </div>
    </div>
</div>
</body>
 
</html>
