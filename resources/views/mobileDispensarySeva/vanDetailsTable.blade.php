
<html>
 
<head>
 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.js"></script> 
    <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap.min.js"></script>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

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
    

     $(document).ready(function() {
        $('#example').DataTable();
    } );    
     
   function backButtonfunction()
      {
          window.location.href = "http://13.235.105.204/api/webOptionView";
      } 
	
   
</script>
</head>
<body>
<div class="container">

<div class="row" >
<h3 align="center">Vehicles Details </h3>	
</div>
<br>
    <!--div class="row" style="margin-left: 25%;" id="buttonRow">
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
	</div-->
    <div class="row">
        <div class="col-sm-2">
            
            <div  class="form-group">
          <input type="button" class="btn btn-success" value="Back" onclick="backButtonfunction()"/>
          </div>
        </div>
    </div>  	
   <div class="row" id="">
        <div class="">
            <table id="example" class="datatable table table-striped table-bordered" style="width:100%">
              <thead>
                <tr>
                  <th class="th-sm">Volunteers Name

                  </th>
                  <th class="th-sm">Volunteers Mobile Number

                  </th>
                  <th class="th-sm">Vehicle Owners Name 

                  </th>
                  <th class="th-sm">Vehicle Owners Mobile Number

                  </th>
                  <th class="th-sm">Vehicle Owners Pan Number

                  </th>
                  <th class="th-sm">Vehicle Registration Number

                  </th>
                  <th class="th-sm">BJS Vehicle Number

                  </th>
                </tr>
              </thead>
              <tbody>
                    @foreach($vanDetailsList as $data)
                    <tr>
                      <td>{{$data->volunteers_name}}</td>
                      <td>{{$data->volunteers_mobile_number}}</td>
                      <td>{{$data->vehicle_owners_name}}</td>
                      <td>{{$data->vehicle_owners_mobile_number}}</td>
                      <td>{{$data->vehicle_owners_pan_number}}</td>
                      <td>{{$data->vehicle_reg_no}}</td>
                      <td>{{$data->bjs_vehicle_no}}</td>
                      
                    </tr>
                    @endforeach

              </tbody>
              <tfoot>
                <tr>
                  <th>Volunteers Name
                  </th>
                  <th>Volunteers Mobile Number
                  </th>
                  <th>Vehicle Owners Name 
                  </th>
                  <th>Vehicle Owners Mobile Number
                  </th>
                  <th>Vehicle Owners Pan Number
                  </th>
                  <th>Vehicle Registration Number
                  </th>
                  <th>BJS Vehicle Number

                  </th>
                </tr>
              </tfoot>
            </table>  
        </div>
    </div>
</div>
</body>
 
</html>
