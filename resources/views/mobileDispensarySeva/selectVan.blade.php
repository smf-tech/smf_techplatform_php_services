
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

    /* .... Other css rules here ... */

</style>
<script>
         setTimeout(function() {
            $('#showMsg').fadeOut('slow');
         }, 70000);

     
    
	$(document).ready(function(){ 
        $("#Other").hide();
        
        $("#vehicleSheet").hide();
		
		$('#doctor_org').on('change', function() {
    
      if ( $('#doctor_org').val() != 'Other' ) 
		{
			$("#Other").hide();
		} 
		
      if ( $('#doctor_org').val() == 'Other' ) 
		{
			$("#Other").show(); 
		}    
	  });
    }); 


 
	
	function vehicleSheetfunction()
	{
		$("#vehicleSheet").show();
        $("#buttonRow").hide();
	}

    function backButtonfunction()
    {
        $("#buttonRow").show();
        $("#vehicleSheet").hide();
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
	
	function PatientsRegisterfunction()
	{
		var vanCode = accessCookie("vanCode");
          
         
          if(vanCode == '')
          {
            $("#vehicleSheet").show();
            //document.getElementById('patienRegBtn').style.display = "none";
            //document.getElementById('showMsg').innerHTML = 'Please fill in Daily Vehicle Sheet first.';


          }else{
                window.location.href = "http://13.235.105.204/api/showPatientInfoForm";
	       }
	
	}


    function checkCookie()
        {
          var vanCode = accessCookie("vanCode");
          
          if(vanCode == '')
          {
            document.getElementById('patienRegBtn').style.display = "none";
            document.getElementById('showMsg').innerHTML = 'Please fill in Daily Vehicle Sheet first.';
          }        
         
        }
   
</script>
</head>
<body onload="checkCookie()">
<div class="container">

<div class="row" >
<!--h3 align="center">BJS - FORCE MOTORS MOBILE DISPENSARY SEVA</h3-->	
</div>
<br>
    <div class="row" style="margin-left: 25%;" id="buttonRow">
         <div class="col-md-3 col-md-offset-2">
			<div  class="form-group">
				<input type="button" class="btn-lg btn-success" value="Daily Vehicle Sheet" onclick="vehicleSheetfunction()" />
            </div>
			
		</div>
		<div class="col-md-3">
			<div  class="form-group">
				<input type="button" class="btn-lg btn-success" id='patienRegBtn' value="Patients Register" onclick="PatientsRegisterfunction()"/>
            </div>
			
		</div>
	</div>
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div id="showMsg" class="alert-warning">
        
            </div>
        </div>
    </div>	
   <div class="row" id="vehicleSheet">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                           
                <div class="panel-body">
                        
                        <div  class="form-group">
                            <input type="button" class="btn btn-success" value="Back" onclick="backButtonfunction()"/>
                        </div>
                        <h3>Vehicle and Other Details</h3>
                    <form action="http://13.235.105.204/api/loadPatientForm " method="post" onsubmit="return validateForm()" >
                        <legend></legend>
                            <div class="form-group">
                                <label for="vanCode">Select Van</label>
                                 <select class="form-control" name="vanCode" id="vanCode"  required = "required" oninvalid="this.setCustomValidity('Please Select Van From the List')" oninput="setCustomValidity('')">
                                     <option value="">---------Choose Van---------</option>
                                      @foreach($vanData as $data)
                                        <option value="{{$data->bjs_vehicle_no}}_{{$data->vehicle_reg_no}}">{{ $data->bjs_vehicle_no}}({{$data->vehicle_reg_no}})</option>
                                    @endforeach
                                    
                                     
                                 </select>    
                                
                             </div>
                             <div class="form-group">
                                 <label for="name">Driver Name</label>
                                 <input type="text" name="driver_name" placeholder="driver name" required = "required" class="form-control" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Driver Name')" oninput="setCustomValidity('')"/>
                                
                             </div>
                             <div class="form-group">
                                 <label for="driver_phone">Driver Mobile Number</label>
                                 <input type="text" required = "required" name="driver_phone" placeholder="driver mobile number" class="form-control" pattern= "[0-9]+" minlength="10" maxlength="10" oninvalid="this.setCustomValidity('Please Enter Valid Driver Mobile Number')" oninput="setCustomValidity('')"/>
                                
                             </div>
                             <div class="form-group">
                                 <label for="doctor_org">Doctor supported by</label>
                                 <select class="form-control" required = "required" name="doctor_org" id="doctor_org"  oninvalid="this.setCustomValidity('Please Select Docter Supported by From the List')" oninput="setCustomValidity('')">
                                     <option value="">---------Choose--------</option>
                                     <option value="BJS">BJS</option>
                                     <option value="Other">Other</option>
                                 </select>  
                             </div>
							 
                             <div class="form-group" id="Other" >
                                 <label for="doctor_name">Other</label>
                                 <input type="text"  name="Other" placeholder="Other" class="form-control" oninvalid="this.setCustomValidity('Please Enter Other Company Name')" oninput="setCustomValidity('')"/>
                             </div>
                             <div class="form-group" id="DoctorName" >
                                 <label for="doctor_name">Doctor Name</label>
                                 <input type="text" required = "required" name="doctor_name" placeholder="Doctor Name" class="form-control" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Doctor Name')" oninput="setCustomValidity('')"/>
                             </div>
                             <div class="form-group" id="DoctorMobileNo" >
                                 <label for="doctor_phone">Doctor Mobile No.</label>
                                 <input type="text" required = "required"  name="doctor_phone" placeholder="Doctor Mobile no." class="form-control"pattern= "[0-9]+" minlength="10" maxlength="10" oninvalid="this.setCustomValidity('Please Enter Valid Doctor Mobile Number')" oninput="setCustomValidity('')"/>
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
