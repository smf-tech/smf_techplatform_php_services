<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meet PDF</title>
    <link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
    <style type="text/css">
        body,html{padding:0; margin:0;height:100%;width:100%;}

        body {
           background: url('http://13.235.124.3/images/background.png');
              
            background-repeat: no-repeat; 
            background-position: top 0px left 0px;
            font-family: 'Roboto' !important;
 			font-size: 0.8em !important;
 			line-height: 1.9em !important;
        }

        .seprator{
            	width: 5px !important;
            }


        .invoice table {
           /* background-color: #c4c4c4;
            */
            margin: 15px;
        }

        .invoice h3 {
            margin-left: 15px;
        }

        .information {
            background-color: #60A7A6;
            color: #FFF;
        }

        .information .logo {
            margin: 5px;
        }

        .textContent{
            /*background-color: #c4c4c4;*/
            margin-left: 0 !important;
            padding-right: 10px !important;
            width: 55% !important;
            border: 0px solid red;
        }
        .information table {
            padding: 10px;
        }
        .invoice img{
           
           /* vertical-align: top;
            position: absolute;*/
            width: 600px;
            height: 623px;
            border: 1px solid #c4c4c4;
            border: 5px solid #fff;
			border-radius: 35px;
        }

        .cardRow{

            padding: 50px 30px !important; 
        }

        .invoice 
        {
        	padding: 50px 30px !important;
        }

        .lable {
             
            color: #000000;
        }

        .text_bold_red {
           
            color: #000000;
        }
        .text_bold_black {
            
            color: #000000;
        }
        .page-break {
        page-break-after: always;
        }
        
        .divider {
          border-bottom : 3px solid red ;
          height: 10px;
          margin: 10;
        }

        .rightTable{
        	border: 0px solid green;
        	background-color: #ffeec8 !important;
        	font-size: 1em !important;
 			line-height: 1.4em !important;
			    margin-left:45px;
			    padding: 25px 30px 25px 40px;
			    border-radius: 40px;
			    margin-top: 20px;
        }

        .leftTable{

        	word-wrap: break-word !important;
        	border: 0px solid red;
        	margin-left: 5px !important;
        }

        .badgeSpan{

        	background-color:#155eb3;
			color:#fff !important;;
			width: 220px !important;
			height: 80px !important;
			right: 0 !important;
			padding:60px !important 
			overflow: hidden;
			border-radius: 5px;
			/*margin-left: 31.3rem;
			margin-top: -2.9rem;
			text-align: center;
			top: 27px;
			right: 0rem !important;
			padding-top: 5px;
			line-height: 25px;*/
			
        }

		
    </style>

</head>
<body>

<div class="invoice" >
    <!--h3>Invoice specification #123</h3-->
    <table width="100%" border="0px">
        <thead>
       
        </thead>
        <tbody>
        @if(isset($userData['male']))
        	
            @foreach($userData['male'] as $item)    
            <tr class="cardRow">
            	<table border="0" width="98%">
            		<tr>
            			<td width="25%" bgcolor="" align="left"  rowspan="2" valign="top">
            				<table width="100%" hight="100%" bgcolor="" border="0"class="leftTable" >
		                     	<tr>
		                     		<td colspan="2" valign="top">
		                     			@if($item['other_marital_information']['profile_image'])
                        
					                       <img src="{{$item['profileImage']}}"> 
					                    @else 
					                         <img src="{{ base_path() }}/resources/assets/images/profile.png" />
					                        
					                     @endif 

		                     		</td>
		                     	</tr>
		                     	<tr>
		                     		<td align="center" colspan="2"><bold class="">{{ $item['badge']??'-'}}</bold></td>
		                     	</tr>
            					<tr>
            						<td valign="top">Name</td>
            						
            						<td valign="top">{{$item['personal_details']['first_name'] }}
                                
                            			{{ $item['personal_details']['last_name'] }}
                            		</td>
            					</tr>

            					<tr>
            						<td valign="top">City</td>
            						
            						<td valign="top">{{$item['residential_details']['city'] }}
                                
                            			
                            		</td>
            					</tr>

            					<tr>
            						<td valign="top">Mob No.</td>
            						
            						<td valign="top">	{{$item['residential_details']['primary_phone']}}
                            		</td>
            					</tr>

            					<tr>
            						<td valign="top">Email </td>
            						
            						<td valign="top">	{{$item['residential_details']['primary_email_address']}}
                            		</td>
            					</tr>

            					<tr>
            						<td valign="top">Marital Status </td>
            						
            						<td valign="top">	{{$item['personal_details']['marital_status']}}
                            		</td>
            					</tr>

            					<tr>
            						<td valign="top">Address</td>
            						
            						<td valign="top">{{$item['personal_details']['first_name'] }}
                                
                            			{{ $item['personal_details']['last_name'] }}
                            		</td>
            					</tr>

            					

            					<tr>
            						<td valign="top">About me</td>
            						
            						<td valign="top">	{{substr($item['other_marital_information']['about_me'],0,30)}}
                            		</td>
            					</tr>

            				</table>

            			</td>

            			<td width="75%" valign="top" align="center">
            				
            				<table width="95%" border="0"  class="rightTable">
            					<tr>

								    <td class="lable">Birth Date: <span class="text_bold_red">{{ $item['birthDate'] }}<span> </td>
								    <td class="lable textContent">Birth Time : <span class="text_bold_black"> {{$item['personal_details']['birth_time'] }}</span>
								    	
								    </td>
								</tr>
								<tr>
								    <td class="lable">Birth Place: <span class="text_bold_red">{{ $item['personal_details']['birth_city'] }}<span> </td>
								    <td class="lable textContent">Blood Group  : <span class="text_bold_black"> {{ $item['personal_details']['blood_group'] }}</span></td>
								</tr>

								<tr>
								    <td class="lable">Height : <span class="text_bold_red">{{ $item['personal_details']['height'] }}<span> </td>
								    <td class="lable textContent">Weight : <span class="text_bold_black"> {{ $item['personal_details']['weight'] }}</span></td>
								</tr>

								<tr>
								    <td class="lable">Complexion : <span class="text_bold_red">{{ $item['personal_details']['complexion'] }}<span> </td>
								    <td class="lable textContent">Manglik : <span class="text_bold_black"> {{ $item['personal_details']['is_manglik'] }}</span></td>
								</tr>

								<tr>
								    <td class="lable">Horoscope Match : <span class="text_bold_red">{{ $item['personal_details']['match_patrika'] }}<span> </td>
								    <td class="lable textContent">Jain Section : <span class="text_bold_black"> {{$item['personal_details']['sect']}}</span></td>
								</tr>

								<tr>
								    <td colspan="2" class="lable">Sub Sect : <span class="text_bold_red">{{ $item['personal_details']['sub_cast']??'-' }}<span> </td>
								    
								</tr>

								<tr>
								    <td colspan="2" class="lable">Sakha-Gotra : <span class="text_bold_red">{{ 
                                        $item['family_details']['gotra']['self_gotra'] }}, 
                                       {{ $item['family_details']['gotra']['mama_gotra']}}, 
                                        {{$item['family_details']['gotra']['dada_gotra']}}, 
                                        {{$item['family_details']['gotra']['nana_gotra'] 

                                    }}<span> </td>
								    
								</tr>

								<tr>
								    <td colspan="2" class="lable">Education : <span class="text_bold_red">{{   $item['educational_details']['education_level'] }},
                                        {{$item['educational_details']['qualification_degree']}}<span> </td>
								    
								</tr>

								<tr>
								    <td class="lable">Professional Qualification : <span class="text_bold_red">{{ $item['educational_details']['qualification_degree'] }}<span> </td>
								    <td class="lable textContent">Profession Occupation : <span class="text_bold_black">{{$item['occupational_details']['occupation']}}</span></td>
								</tr>

								<tr>
								    <td colspan="2" class="lable">Income : <span class="text_bold_red">{{$item['educational_details']['income']}}<span> </td>
								    
								</tr> 

								<tr>
								    <td colspan="2" class="lable">Expectations about Life  Partner : <span class="text_bold_red">{{ substr($item['other_marital_information']['expectation_from_partner'],0,100) }}<span> </td>
								    
								</tr>

		                		

		                	</table>
            				
            			</td>
            		</tr>
            		<tr>
            			<!-- <td width="25%" valign="top">
            			

            			</td> -->
            			<td width="75%" valign="top" align="center">

            				<table width="95%" border="0"  class="rightTable">
            					<tr>
								    <td class="lable">Father's Name : <span class="text_bold_red">{{ $item['family_details']['father_name'] }}<span> </td>
								    <td class="lable textContent">Occupation : <span class="text_bold_black"> {{ $item['family_details']['father_occupation'] }}</span></td>
								</tr>
								
								<tr>
								    <td class="lable">Mother's Name : <span class="text_bold_red"> {{ $item['family_details']['mother_name'] }}</span> </td>
								    <td class="lable textContent">Occupation : <span class="text_bold_black">{{ $item['family_details']['mother_occupation'] }}</span> </td>
								</tr>
								<tr>
								    <td colspan="2" class="lable">Brothers : <span class="text_bold_red"> {{ $item['family_details']['brother_count'] }}</span> </td>
								   
								</tr>
								<tr>
								    <td colspan="2" class="lable">Sisters : <span class="text_bold_red"> {{ $item['family_details']['sister_count'] }}</span> </td>
								   
								</tr>
								<tr>
								    <td colspan="2" class="lable">Postal Address : <span class="text_bold_black">{{ $item['residential_details']['address'] }}</span></td>
								</tr>
								<tr >
								    <td class="lable">Mob No.  : 
								            <span class="text_bold_black">{{$item['residential_details']['secondary_phone']}},
								            {{$item['residential_details']['primary_phone']}}</span></td>
								    <td class="lable textContent">Email : <span class="text_bold_black">{{ $item['residential_details']['primary_email_address'] }}</span></td>
								    
								</tr>
								<tr>
								    
								      <td colspan="2" align="center" class="inputTable" >
								    	<table width="100%"  border="0" >
								    		<tr>
								    			<td width="25%" align="left" class="lable">Want to meet? : </td>
								    			<td width="25%" align="center"><input type="checkbox" name=""></td>
								    			<td width="25%" align="center"><input type="checkbox" name=""></td>
								    			<td width="25%" align="center"> <input type="checkbox" name=""></td>
								    		</tr>
								    		<tr>
								    			<td width="25%" align="center">&nbsp;</td>
								    			<td width="25%" align="center">Definitely</td>
								    			<td width="25%" align="center">May be</td>
								    			<td width="25%" align="center">Don't</td>
								    		</tr>
								    	</table>

								    </td>
								</tr>


            				</table>

            			</td>
            		</tr>
            		

            	</table>
            
            </tr>
            
            
            @endforeach
          
        @endif


        </tbody>

       
    </table>
</div>


<div class="invoice" >
    <!--h3>Invoice specification #123</h3-->
    <table width="100%" border="0px">
        <thead>
       
        </thead>
        <tbody>
        @if(isset($userData['female']))
            
            @foreach($userData['female'] as $item)    
            <tr class="cardRow">
                <table border="0" width="98%">
                    <tr>
                        <td width="25%" bgcolor="" align="left"  rowspan="2" valign="top">
                            <table width="100%" hight="100%" bgcolor="" border="0"class="leftTable" >
                                <tr>
                                    <td colspan="2" valign="top">
                                        @if($item['other_marital_information']['profile_image'])
                        
                                           <img src="{{$item['profileImage']}}"> 
                                        @else 
                                             <img src="{{ base_path() }}/resources/assets/images/profile.png" />
                                            
                                         @endif 

                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" colspan="2"><bold class="">{{ $item['badge']??'-'}}</bold></td>
                                </tr>
                                <tr>
                                    <td valign="top">Name</td>
                                    
                                    <td valign="top">{{$item['personal_details']['first_name'] }}
                                
                                        {{ $item['personal_details']['last_name'] }}
                                    </td>
                                </tr>

                                <tr>
                                    <td valign="top">City</td>
                                    
                                    <td valign="top">{{$item['residential_details']['city'] }}
                                
                                        
                                    </td>
                                </tr>

                                <tr>
                                    <td valign="top">Mob No.</td>
                                    
                                    <td valign="top">   {{$item['residential_details']['primary_phone']}}
                                    </td>
                                </tr>

                                <tr>
                                    <td valign="top">Email </td>
                                    
                                    <td valign="top">   {{$item['residential_details']['primary_email_address']}}
                                    </td>
                                </tr>

                                <tr>
                                    <td valign="top">Marital Status </td>
                                    
                                    <td valign="top">   {{$item['personal_details']['marital_status']}}
                                    </td>
                                </tr>

                                <tr>
                                    <td valign="top">Address</td>
                                    
                                    <td valign="top">{{$item['personal_details']['first_name'] }}
                                
                                        {{ $item['personal_details']['last_name'] }}
                                    </td>
                                </tr>

                                

                                <tr>
                                    <td valign="top">About me</td>
                                    
                                    <td valign="top">   {{substr($item['other_marital_information']['about_me'],0,30)}}
                                    </td>
                                </tr>

                            </table>

                        </td>

                        <td width="75%" valign="top" align="center">
                            
                            <table width="95%" border="0"  class="rightTable">
                                <tr>

                                    <td class="lable">Birth Date: <span class="text_bold_red">{{ $item['birthDate'] }}<span> </td>
                                    <td class="lable textContent">Birth Time : <span class="text_bold_black"> {{$item['personal_details']['birth_time'] }}</span>
                                        
                                    </td>
                                </tr>
                                <tr>
                                    <td class="lable">Birth Place: <span class="text_bold_red">{{ $item['personal_details']['birth_city'] }}<span> </td>
                                    <td class="lable textContent">Blood Group  : <span class="text_bold_black"> {{ $item['personal_details']['blood_group'] }}</span></td>
                                </tr>

                                <tr>
                                    <td class="lable">Height : <span class="text_bold_red">{{ $item['personal_details']['height'] }}<span> </td>
                                    <td class="lable textContent">Weight : <span class="text_bold_black"> {{ $item['personal_details']['weight'] }}</span></td>
                                </tr>

                                <tr>
                                    <td class="lable">Complexion : <span class="text_bold_red">{{ $item['personal_details']['complexion'] }}<span> </td>
                                    <td class="lable textContent">Manglik : <span class="text_bold_black"> {{ $item['personal_details']['is_manglik'] }}</span></td>
                                </tr>

                                <tr>
                                    <td class="lable">Horoscope Match : <span class="text_bold_red">{{ $item['personal_details']['match_patrika'] }}<span> </td>
                                    <td class="lable textContent">Jain Section : <span class="text_bold_black"> {{$item['personal_details']['sect']}}</span></td>
                                </tr>

                                <tr>
                                    <td colspan="2" class="lable">Sub Sect : <span class="text_bold_red">{{ $item['personal_details']['sub_cast']??'-' }}<span> </td>
                                    
                                </tr>

                                <tr>
                                    <td colspan="2" class="lable">Sakha-Gotra : <span class="text_bold_red">{{ 
                                        $item['family_details']['gotra']['self_gotra'] }}, 
                                       {{ $item['family_details']['gotra']['mama_gotra']}}, 
                                        {{$item['family_details']['gotra']['dada_gotra']}}, 
                                        {{$item['family_details']['gotra']['nana_gotra'] 

                                    }}<span> </td>
                                    
                                </tr>

                                <tr>
                                    <td colspan="2" class="lable">Education : <span class="text_bold_red">{{   $item['educational_details']['education_level'] }},
                                        {{$item['educational_details']['qualification_degree']}}<span> </td>
                                    
                                </tr>

                                <tr>
                                    <td class="lable">Professional Qualification : <span class="text_bold_red">{{ $item['educational_details']['qualification_degree'] }}<span> </td>
                                    <td class="lable textContent">Profession Occupation : <span class="text_bold_black">{{$item['occupational_details']['occupation']}}</span></td>
                                </tr>

                                <tr>
                                    <td colspan="2" class="lable">Income : <span class="text_bold_red">{{$item['educational_details']['income']}}<span> </td>
                                    
                                </tr> 

                                <tr>
                                    <td colspan="2" class="lable">Expectations about Life  Partner : <span class="text_bold_red">{{ substr($item['other_marital_information']['expectation_from_partner'],0,100) }}<span> </td>
                                    
                                </tr>

                                

                            </table>
                            
                        </td>
                    </tr>
                    <tr>
                        <!-- <td width="25%" valign="top">
                        

                        </td> -->
                        <td width="75%" valign="top" align="center">

                            <table width="95%" border="0"  class="rightTable">
                                <tr>
                                    <td class="lable">Father's Name : <span class="text_bold_red">{{ $item['family_details']['father_name'] }}<span> </td>
                                    <td class="lable textContent">Occupation : <span class="text_bold_black"> {{ $item['family_details']['father_occupation'] }}</span></td>
                                </tr>
                                
                                <tr>
                                    <td class="lable">Mother's Name : <span class="text_bold_red"> {{ $item['family_details']['mother_name'] }}</span> </td>
                                    <td class="lable textContent">Occupation : <span class="text_bold_black">{{ $item['family_details']['mother_occupation'] }}</span> </td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="lable">Brothers : <span class="text_bold_red"> {{ $item['family_details']['brother_count'] }}</span> </td>
                                   
                                </tr>
                                <tr>
                                    <td colspan="2" class="lable">Sisters : <span class="text_bold_red"> {{ $item['family_details']['sister_count'] }}</span> </td>
                                   
                                </tr>
                                <tr>
                                    <td colspan="2" class="lable">Postal Address : <span class="text_bold_black">{{ $item['residential_details']['address'] }}</span></td>
                                </tr>
                                <tr >
                                    <td class="lable">Mob No.  : 
                                            <span class="text_bold_black">{{$item['residential_details']['secondary_phone']}},
                                            {{$item['residential_details']['primary_phone']}}</span></td>
                                    <td class="lable textContent">Email : <span class="text_bold_black">{{ $item['residential_details']['primary_email_address'] }}</span></td>
                                    
                                </tr>
                                <tr>
                                    
                                      <td colspan="2" align="center" class="inputTable" >
                                        <table width="100%"  border="0" >
                                            <tr>
                                                <td width="25%" align="left" class="lable">Want to meet? : </td>
                                                <td width="25%" align="center"><input type="checkbox" name=""></td>
                                                <td width="25%" align="center"><input type="checkbox" name=""></td>
                                                <td width="25%" align="center"> <input type="checkbox" name=""></td>
                                            </tr>
                                            <tr>
                                                <td width="25%" align="center">&nbsp;</td>
                                                <td width="25%" align="center">Definitely</td>
                                                <td width="25%" align="center">May be</td>
                                                <td width="25%" align="center">Don't</td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>


                            </table>

                        </td>
                    </tr>
                    

                </table>
            
            </tr>
            
            
            @endforeach
          
        @endif


        </tbody>

       
    </table>
</div>


</body>
</html>