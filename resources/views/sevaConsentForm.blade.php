
<html>
<title>Seva Consent Form</title>
<head>
<meta http-equiv="refresh" content="60; http://the-octopus.com/api/sevaConsentForm" />

 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
 <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
 
<script>
        $(document).ready(function() {
          $('#agreement').hide(); 
        });
		function read_button(){
		 
		  $('#form_id').hide();
		  $('#agreement').show();
		  
		}
		function back_button(){
		  $('#form_id').show();
		  $('#agreement').hide();
		}
		
      </script> 
	  
<style>
.border-class
{
  border:#dcd0d0 solid;
  margin:20px;
  padding:20px;
}
body {
	font-size:50px;
}

.form-group-lg .form-control {
    height: 150px;
	font-size: 40px;
}

.form-check-input{
	width: 40px;
    height: 40px;
}

.container{
	width: 1325px;
}
</style>
</head>
<body>

	<div class="container" id="agreement">
	<div class="border-class"> 
		<div class="panel panel-default">
		<label for="" align="center">Terms of Services </label>
		  <!--<div class="panel-heading" align="center"><h2>Agreement</h2></div> -->
		</div>
		<button type="button" class="btn btn-primary btn-lg" onclick="back_button()"  style="font-size: 45px;">Back</button>
		<br/>
		<br/>
		<textarea class="form-control custom-control input-lg" rows="50" readonly style="resize:none;overflow:hidden;font-size: 37px;" >
I the undersigned

Doctor have been impressed and inspired by  the Mobile Dispensary SEVA project which is providing voluntary  primary health care in coordination with the Govt to the needy in the current difficult times caused by the COVID 19 outbreak . I want to join the effort by offering my medical knowledge and experience as a pure volunteer. However, it is correctly  thought to be in good order that I give the following undertaking. 


I fully understand the goal of this mission and the workload and commit to working on field for a minimum of 8 hours per day.

Owing to the shortage of healthcare professionals I shall stay fully dedicated to this cause at all times on duty and try to meet an opd goal of 200 patients per day but not limit my service by that number. 

Due to lockdown to make my travel less cumbersome and to protect my family from undue exposure to the frontline workers like me, the organizers  have been kind enough  to offer me  accommodation,  free of cost, for the period of my service with them.

I ensure to maintain  the property  assigned for my stay in good condition and shall not cause any damage to the same.
I understand  I am given this accommodation  only for the time of my service to this mission after which I shall be liable to  handover vacant and peaceful  possession  of the premises the day my duty ends.
I shall have no rights or  claims, whatsoever,  on the accomodation  property,   provided during my service.  
I abide and agree to any change in accommodation  if required during my service.
 
I am grateful for to the Rs 1500/_ honorarium amount I will receive  per day as a token for my services. 

I hereby indemnify the above said entities  for any discomfort , ailment , unseen circumstance during my service.

I have with my free will joined the cause and understand the implications  of being in covid positive zones.

If in my duty I  should contract  covid 19 or any other ailment , I shall not hold BJS responsible  for the same.

Sincerely, 

So that I as a doctor and the organizers can all  dedicate ourselves better to the service of the needy.
	</textarea>  
	</div>
	</div>
<div class="container">
 
<form class="border-class" id="form_id"> 

	<div class="panel panel-default">
	  <div class="panel-heading" align="center" ><h2 style="font-size: 45px;">Seva Consent Form</h2></div> 
	</div> 
   <div class="form-group form-group-lg">
   <label for="">Terms of Services </label>
    <textarea class="form-control custom-control input-lg" rows="6" readonly name="agreement_text" id="agreement_tag" placeholder="Agreement size" style="resize:none;overflow:hidden" >
I the undersigned

Doctor have been impressed and inspired by  the Mobile Dispensary SEVA project which is providing voluntary  primary health care in coordination with the Govt to the needy in the current difficult times caused by the COVID 19 outbreak . I want to join the effort by offering my medical knowledge and experience as a pure volunteer. However, it is correctly  thought to be in good order that I give the following undertaking. 
	</textarea>     
    <span class="input-group-addon btn btn-primary" onclick="read_button()" style="background-color:#337ab7;color:#fff"><h2>Read More</h2></span>
   </div>
  <div class="form-group form-group-lg">
  		<label for="">Name </label>
    <input type="text" class="form-control input-lg" id="name" placeholder="Name" name="name" required="required">
  </div>
  <div class="form-group form-group-lg">
  		<label for="">Mobile NO </label>
    <input type="tel" class="form-control input-lg" id="mobile_no" placeholder="Mobile No." name="mobile_no" title="Mobile Number should be 10 digit only" required="required" pattern="[1-9]{1}[0-9]{9}" >
  </div>
  <div class="form-group form-group-lg">
  		<label for="">City </label>
    <input type="text" class="form-control input-lg" id="city" placeholder="City" name="city">
  </div>
  <div class="form-group form-group-lg">
  		<label for="">Personal id No.(PAN/AADHAR/DL) </label>
    <input type="text" class="form-control input-lg" id="personal_id" placeholder="Personal id No.(PAN/AADHAR/DL)" name="personal_id">
  </div>
  <div class="form-group form-group-lg">
    		<label for="">Registration No.(Only for Doctors) </label>
    <input type="text" class="form-control input-lg" id="registration_no" placeholder="Registration No.(Only for Doctors)" name="registration_no">
  </div>
  <div class="form-group form-group-lg">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="" onclick="ShowHideDiv(this)" required="required">
      <label class="form-check-label" for="gridCheck">
        I agree to above terms and condtions.
      </label>
    </div>
  </div>
  <button type="submit"  id="buttonID" class="btn btn-primary btn-lg" style="font-size: 45px;">Submit</button>
 	<div id="flashmsg" class="alert-success"></div>
</form>
</div>
<script>
if ( window.history.replaceState ) {
  window.history.replaceState( null, null, window.location.href );
}
</script>
<script type="text/javascript">

    $('#form_id').on('submit',function(event){
        event.preventDefault();

        name = $('#name').val();
        mobile_no = $('#mobile_no').val();
        city = $('#city').val();
        personal_id = $('#personal_id').val();
        registration_no = $('#registration_no').val();

        $.ajax({
          url: "http://the-octopus.com/api/savesevaConsentForm",
          type:"POST",
          data:{
			  name:name,
            mobile_no:mobile_no,
            city:city,
            personal_id:personal_id,
            registration_no:registration_no,
          },
          success:function(response){
			  $( '#form_id' ).each(function(){
				this.reset();
			});
			document.getElementById('flashmsg').innerHTML += response ;
			$('#flashmsg').delay(4000).fadeOut();
          },
         });
        });
      </script>
</body>
</html>