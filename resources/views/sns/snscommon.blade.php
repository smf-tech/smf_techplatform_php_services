<html>
<head>
 
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
<meta charset="utf-8">

</head>
<body>
   

<div id="preloader">
  <div id="loader"></div>
</div>
    <div class="row justify-content-center">
        <div class="col-sm-12 certificateBox"> 
            <div class="panel panel-default"> 
                <div class="panel-body"> 
                    <h2><strong>
                    Create Your Topic      
                    </strong></h2>
                    <form action="CreateSnsTopic" method="post" name="myForm" >
                        <div class="form-group">
                            <label for="name">Topic Title</label>
                               <input type="text"  name="topicName"  placeholder="Topic Name" class="form-control" required  />
                        </div> 
                            <input type="submit" id="buttons" class="btn btn-success btn-lg"/>
                    </form>                        
                </div>
            </div>
        </div>
    </div>
	<div class="row justify-content-center">
        <div class="col-sm-12 certificateBox"> 
            <div class="panel panel-default"> 
                <div class="panel-body"> 
                    <h2><strong>
                   Configure Your http to Subscribe 
                    </strong></h2>
                    <form action="subscribe" method="post" name="myForm" > 
						<div class="form-group">
                            <label for="name">http</label>
                               <input type="text"  name="http"  placeholder="Topic Name" class="form-control" required  />
                        </div> 
                            <input type="submit" id="buttons" class="btn btn-success btn-lg"/>
                    </form>                        
                </div>
            </div>
        </div>
    </div>
	<div class="row justify-content-center">
        <div class="col-sm-12 certificateBox"> 
            <div class="panel panel-default"> 
                <div class="panel-body"> 
                    <h2><strong>
                   Configure Your Message
                    </strong></h2>
                    <form action="publishMessage" method="post" name="myForm" >
                        <div class="form-group">
                            <label for="name">Subject</label>
                              <input type="text"  name="subject"  placeholder="Topic Name" class="form-control" required  />
                        </div> 
						<div class="form-group">
                            <label for="name">Message</label>
                               <input type="text"  name="message"  placeholder="Topic Name" class="form-control" required  />
                        </div> 
                            <input type="submit" id="buttons" class="btn btn-success btn-lg"/>
                    </form>                        
                </div>
            </div>
        </div>
    </div>
</div>

 


</body>
 
</html>
