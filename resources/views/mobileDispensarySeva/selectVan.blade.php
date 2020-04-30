
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
            $('#flashmsg').fadeOut('slow');
         }, 3000);

   
    
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
	    window.location.href = "http://13.235.105.204/api/showPatientInfoForm";
	
	}

    function PatientsContactDetailsfunction()
    {
        window.location.href = "http://13.235.105.204/api/showPatientContactDetailsForm";
    
    }
	
	function endMeterReading()
	{
		$("#end_meter_reading").on('keyup',function(){

    if(parseFloat($("#start_meter_reading").val()) > parseFloat($("#end_meter_reading").val()))
    {
		$(".error").css("display","block").css("color","red");  
		$("#submit").css("display","none");  
			
       // $("#submit").prop('disabled',false);
    }
    else {
        $(".error").css("display","none");   
        $("#submit").css("display","block");  		
		$("#end_meter_reading").focus(); 
        // $("#submit").prop('disabled',true);        
    }

		});
	}

</script>
</head>
<body >
<div class="container">

<div class="row" >
<!--h3 align="center">BJS - FORCE MOTORS MOBILE DISPENSARY SEVA</h3-->	
</div>
<br>
    <div class="row" style="margin-left: 10%;" id="buttonRow">
         <div class="col-md-4 col-md-offset-2">
			<div  class="form-group">
				<input type="button" class="btn-lg btn-success" value="Van - Area Visit Register" onclick="vehicleSheetfunction()" />
            </div>
			
		</div>
		<div class="col-md-4">
			<div  class="form-group">
				<input type="button" class="btn-lg btn-success" id='patienRegBtn' value="Van - Area Wise Patients Register" onclick="PatientsRegisterfunction()"/>
            </div>
			
		</div>

        <div class="col-md-4">
            <div  class="form-group">
                <input type="button" class="btn-lg btn-success" id='patienRegBtn' value=" Patients Details Register" onclick="PatientsContactDetailsfunction()"/>
            </div>
            
        </div>
	</div>
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div id="showMsg" class="alert-warning">
        
            </div>
        </div>
    </div>	
   <div class="row" >
     <div id="flashmsg" class="alert-success">{{$msg ?? ""}}</div>
        <div class="col-md-8 col-md-offset-2" id="vehicleSheet">
            <div class="panel panel-default">
                           
                <div class="panel-body">
                        
                        <div  class="form-group">
                            <input type="button" class="btn btn-success" value="Back" onclick="backButtonfunction()"/>
                        </div>
                        <h3>Van - Area Visit Register</h3>
                    <form action="http://13.235.105.204/api/loadPatientForm " method="post" onsubmit="return validateForm()" >
                        <legend></legend>
							<!--div class="form-group">
                                 <label for="name">Date</label>
                                 <input type='date' name='date' required = 'required' class="form-control" id='datetimepicker4' oninvalid="this.setCustomValidity('Please Select Date From Calender')" oninput="setCustomValidity('')"/>
                             </div-->		
                            <div class="form-group">
                                <label for="vanCode">Select Van / वैन का चयन करें </label>
                                 <select class="form-control" name="vanCode" id="vanCode"  required = "required" oninvalid="this.setCustomValidity('Please Select Van From the List')" oninput="setCustomValidity('')">
                                     <option value="">---------Choose Van---------</option>
                                      @foreach($vanData as $data)
                                        <option value="{{$data->bjs_vehicle_no}}_{{$data->vehicle_reg_no}}">{{ $data->bjs_vehicle_no}}({{$data->vehicle_reg_no}})</option>
                                    @endforeach
                                 </select>    
                             </div>
                             <div class="form-group">
                                 <label for="name">Driver Name / ड्राइवर का नाम</label>
                                 <input type="text" name="driver_name" placeholder="driver name" required = "required" class="form-control" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Driver Name')" oninput="setCustomValidity('')"/>
                             </div>
                             <div class="form-group">
                                 <label for="driver_phone">Driver Mobile Number / 
ड्राइवर मोबाइल नंबर</label>
                                 <input type="text" required = "required" name="driver_phone" placeholder="driver mobile number" class="form-control" pattern= "[0-9]+" minlength="10" maxlength="10" oninvalid="this.setCustomValidity('Please Enter Valid Driver Mobile Number')" oninput="setCustomValidity('')"/>
                                
                             </div>
                             <!--div class="form-group">
                                 <label for="day_start_time">Day Start Time / 
दिन की शुरुआत का समय</label>

                                 <input type="time" required = "required" name="day_start_time" placeholder="" class="form-control" />

                             </div>
							 <div class="form-group" id="startLocation" >
                                 <label for="startLocation">Day Start Location / दिन की शुरुआत का स्थान</label>
                                 <input type="text" required = "required" name="start_location" placeholder="Pimple Gurav" class="form-control" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Start Location')" oninput="setCustomValidity('')"/>
                             </div-->
							 
							 <div class="form-group" id="startMeterReading" >
                                 <label for="startMeterReading">Start Meter Reading / शुरुआत की मीटर  रीडिंग     </label>
                                 <input type="number" required = "required" name="start_meter_reading" placeholder="Start Meter Reading" class="form-control" id="start_meter_reading" oninvalid="this.setCustomValidity('Please Enter Valid Start Meter Reading')" oninput="setCustomValidity('')"/>
                             </div>
							 <!--div class="form-group" id="VisitedArea1" >
                                 <label for="VisitedArea1">Visited Area 1 / देखा गया क्षेत्र 1</label>
                                 <input type="text" required = "required" name="visited_area_1" placeholder="Visited Area 1" class="form-control" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Visited Area 1')" oninput="setCustomValidity('')"/>
                             </div>
							 <div class="form-group" id="Area1VisitDuration" >
                                 <label for="Area1VisitDuration">Area 1 Visit Duration(Hours) / क्षेत्र 1 की दौरा अवधि (घंटे)</label>
                                 <input type='number' step='0.01' required = "required" name="area_1_visit_duration" placeholder="Area 1 Visit Duration(Hours)" class="form-control" pattern="[0-9]" oninvalid="this.setCustomValidity('Please Enter Valid Area 1 Visit Duration')" oninput="setCustomValidity('')"/>
                             </div>
							 <div class="form-group" id="VisitedArea2" >
                                 <label for="VisitedArea2">Visited Area 2 / देखा गया क्षेत्र 2 </label>
                                 <input type="text"  name="visited_area_2" placeholder="Visited Area 2" class="form-control" />
                             </div>
							 <div class="form-group" id="Area2VisitDuration" >
                                 <label for="Area2VisitDuration">Area 2 Visit Duration(Hours) / क्षेत्र 2 की दौरा अवधि (घंटे)</label>
                                 <input type="number" step='0.01' name="area_2_visit_duration" placeholder="Area 2 Visit Duration(Hours)" class="form-control" />
                             </div>
							 <div class="form-group" id="VisitedArea3" >
                                 <label for="VisitedArea3">Visited Area 3 / देखा गया क्षेत्र 3</label>
                                 <input type="text" step='0.01'  name="visited_area_3" placeholder="Visited Area 3" class="form-control" />
                             </div>
							 
							 <div class="form-group" id="Area3VisitDuration" >
                                 <label for="Area3VisitDuration">Area 3 Visit Duration(Hours) / क्षेत्र 3 की दौरा अवधि (घंटे)</label>
                                 <input type="number" step='0.01'  name="area_3_visit_duration" placeholder="Area 3 Visit Duration(Hours)" class="form-control"/>
                             </div>
							 
							 <div class="form-group" id="VisitedArea4" >
                                 <label for="VisitedArea4">Visited Area 4 / देखा गया क्षेत्र 4</label>
                                 <input type="text"  name="visited_area_4" placeholder="Visited Area 4" class="form-control" />
                             </div>
							 
							 <div class="form-group" id="Area4VisitDuration" >
                                 <label for="Area4VisitDuration">Area 4 Visit Duration(Hours) / क्षेत्र 4 की दौरा अवधि (घंटे)</label>
                                 <input type="number" step='0.01'  name="area_4_visit_duration" placeholder="Area 4 Visit Duration(Hours)" class="form-control" />
                             </div>
							 
							 <div class="form-group" id="EndLocation" >
                                 <label for="EndLocation">Day End Location / दिन के अंत स्थान</label>
                                 <input type="text" name="end_location" placeholder="BJS HO" class="form-control" required = "required" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid End Location')" oninput="setCustomValidity('')"/>
                             </div-->
							 
							 <div class="form-group" id="EndMeterReading" >
                                 <label for="EndMeterReading">End Meter Reading / अंत की मीटर  रीडिंग</label>
                                 <input type="number" required = "required" name="end_meter_reading" placeholder="End Meter Reading" class="form-control" id="end_meter_reading" onkeypress="endMeterReading()" oninvalid="this.setCustomValidity('Please Enter Valid End Meter Reading')" oninput="setCustomValidity('')"/>
                             </div>
							 <div class="error" style="display:none">End Meter Reading should not be less than Start Meter Reading</div>
                             <!--div class="form-group">
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
                             </div-->
                          
                            <br/>
                            <div id="levelContainer"  class="form-group">
                                   
                            </div>
							<div  > 
                            <input type="submit" id="submit" class="btn btn-success"/>
							</div>
                         </form>                        
                </div>
            </div>
        </div>
    </div>
</div>
</body>
 
</html>
