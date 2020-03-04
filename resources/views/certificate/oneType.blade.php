
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
     
        body {font-family:Noto Sans !important;}
      .font_alpha { font-family: "Roboto";}`

    /* .... Other css rules here ... */

</style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">

                <div class="panel-body">
                   
                        <h3>Download Certificate</h3>
                    <form action="downloadCertificate" method="post">
                          
                        <legend></legend>

                        	<div class="form-group">
                                 <label for="name">Teacher Code</label>
                                 <input type="text" name="teacerCode" placeholder="code" class="form-control"/>
                                
                             </div>
                             <div>
                             	
                             	<input type="hidden" name="certificateType"  value="{{$type}}">
                             </div>
                             <!--div class="form-group">
                                 <label for="name">Name</label>
                                 <input type="text" name="Username" placeholder="name" class="form-control"/>
                                
                             </div>

                             <div class="form-group">
                                 <label for="taluka">Taluka</label>
                                 <input type="text" name="Taluka" placeholder="Taluka" class="form-control"/>
                                
                             </div>

                             <div class="form-group">
                                 <label for="district">District</label>
                                 <input type="text" name="District" placeholder="District" class="form-control"/>
                                
                             </div>

                             <div class="form-group">
                                 <label for="school">School Name</label>
                                 <input type="text" name="schoolName" placeholder="schoolName" class="form-control"/>
                                
                             </div>

                             <div class="form-group">
                                 <label for="certificateType">Certificate Type</label>
                                 <!-- <input type="text" name="certificateType" placeholder="certificateType" class="form-control"/> -->

                                 <!-- select class="form-control" name="certificateType" id="certificateType">
                                     <option value="">---------Choose Team---------</option>
                                     <option value="Prerak">Prerak</option>
                                     <option value="Shikshak">Shikshak</option>
                                     <option value="Poster">Poster</option>
                                     <option value="Anubhav">Anubhav</option>
                                 </select>    
                                
                             </div -->
                          
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
