
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
	function insertVehiclefunction()
	{
		window.location.href = "http://13.235.105.204/api/insertVanForm";
	}

    function vehicleSheetfunction()
    {
        window.location.href = "http://13.235.105.204/api/vanDetailsList";
    }

    function PatientSheetfunction()
    {
        window.location.href = "http://13.235.105.204/api/patientList";
    }

    function DailyVehicleRegfunction()
    {
        window.location.href = "http://13.235.105.204/api/dailyVehicleRegister";
    }

    function PatientDetailsRegister()
    {
        window.location.href = "http://13.235.105.204/api/patientContactDetailsList";
    }
   
</script>
</head>
<body >
<div class="container">

<div class="row" >
<!--h3 align="center">BJS - FORCE MOTORS MOBILE DISPENSARY SEVA</h3-->	
</div>
    <br/>
    <div class="row" style="">
        <div class="col-sm-4 col-sm-offset-1">
            <div  class="form-group">
                <input type="button" class="btn-lg btn-success" value="Add Vehicle" onclick="insertVehiclefunction()" />
            </div>
            
        </div>
        <div class="col-sm-4 col-sm-offset-1">
            <div  class="form-group">
                <input type="button" class="btn-lg btn-success" value="Vehicle List" onclick="vehicleSheetfunction()" />
            </div>
        </div>
    </div>    
    <br/>
    <div class="row" style="">    
        <div class="col-sm-4 col-sm-offset-1">
            <div  class="form-group">
                <input type="button" class="btn-lg btn-success" id='vehicleRegBtn' value="Van - Area Visit Register" onclick="DailyVehicleRegfunction()"/>
            </div>
            
        </div>
        <div class="col-sm-4 col-sm-offset-1">
            <div  class="form-group">
                <input type="button" class="btn-lg btn-success" id='patienRegBtn' value="Van - Area Wise Patients Register" onclick="PatientSheetfunction()"/>
            </div>
            
        </div>
        

	</div>
     <br/>
    <div class="row" style="">    
        <div class="col-sm-4 col-sm-offset-1">
            <div  class="form-group">
                <input type="button" class="btn-lg btn-success" id='patienRegetailsBtn' value="Patient Details Register" onclick="PatientDetailsRegister()"/>
            </div>
            
        </div>
        
        

    </div>
    
</div>
</body>
 
</html>
