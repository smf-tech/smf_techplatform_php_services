
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
        

</style>
<script>
    

     $(document).ready(function() {
        $('#example').DataTable();
    } );    
     
   function backButtonfunction()
      {
          window.location.href = "http://13.235.105.204/api/webOptionView";
      }  
 

  // function addDepotButtonfunction()
      // {
          // window.location.href = "http://13.235.105.204/api/insertDepotForm";
      // } 
	
   
</script>
</head>
<body>
<div class="container">

<div class="row" >
<h3 align="center">Seva Consent Details </h3>	
</div>
<br>
  
    <div class="row">
        <div class="col-sm-2">
            
            <div  class="form-group">
                <input type="button" class="btn btn-success" value="Back" onclick="backButtonfunction()"/>
            </div>
        </div>
<!--
        <div class="col-sm-2">
            
            <div  class="form-group">
                <input type="button" class="btn btn-success" value="Add Depot" onclick="addDepotButtonfunction()"/>
            </div>
        </div> -->

    </div>  	
   <div class="row" id="">
        <div class="">
            <table id="example" class="datatable table table-striped table-bordered" style="width:100%">
              <thead>
                <tr>
                  <th class="th-sm">Name

                  </th>
                  <th class="th-sm">Mobile Number

                  </th>
                  <th class="th-sm">City

                  </th>
                  <th class="th-sm">Personal ID

                  </th>
                  <th class="th-sm">Registration Number
 

                  </th>
                </tr>
              </thead>
              <tbody>
                    @foreach($SevaContents as $data)
                    <tr>
                      <td>{{$data->name}}</td>
                      <td>{{$data->mobile_no}}</td>
                      <td>{{$data->city}}</td>
                      <td>{{$data->personal_id}}</td>
                      <td>{{$data->registration_no}}</td>
                      
                    </tr>
                    @endforeach

              </tbody>
              <tfoot>
                <tr>
                  <th>Name
                  </th>
                  <th>Mobile Number
                  </th>
                  <th>City
                  </th>
                  <th>Personal ID
                  </th>
                  <th>Registration Number
                  </th>
                
                </tr>
              </tfoot>
            </table>  
        </div>
    </div>
</div>
</body>
 
</html>
