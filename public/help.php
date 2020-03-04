<html>
<head>
 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
 <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
</head>
<body>
	<div class="container">
  <h2>Role Table</h2>
  <p>Following table shows action according to there roles.</p>            
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Role</th>
        <th>Action</th> 
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>DM</td>
        <td>
			<ol>
				<li> Can create a new machine.</li>
				<li> Can change the status of the machine either to 'Eligible' or 'Not-eligible'.</li>
				<li> Can fill the form of 'Machine MOU Record' (Machine operator profile get created automatically).</li>
				<li> Can assign a machine to a Taluka.</li> 
			</ol>
		</td>
        
      </tr>
      <tr>
        <td>TC</td>
        <td>
			<ol>
				<li> Can create a new structure through 'Structure Master' form.</li>
				<li> Can change the status of the structure to 'Prepared'.</li>
				<li> Can fill the form of 'Community Mobilisation'.</li>
				<li> Can fill the form of 'Structure visit and monitoring record'.</li>
				<li> Can deploy the machine on structure. </li>
				<li> Can shift the machine from one structure to another through 'Machine Shifting record' form.</li>
				<li> Can check and verify the machine working hours through 'Machine visit and validation of working hours record' form.</li>
				<li> Can change the status of the machine to 'Free from Taluka'.</li>
				<li> Can change the status of the structure to 'Closed'.</li>
			</ol>
		</td>
        
      </tr>
      <tr>
        <td>HO-OPS</td>
        <td>
			<ol>
				<li> Read only access to all the above mentioned points.</li>
			</ol>	
		</td>
         
      </tr>
    </tbody>
  </table>
</div>
</body>
</html>
