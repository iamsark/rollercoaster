<!DOCTYPE HTML>
<head>

	<head>
	
		<meta charset="utf-8">
	  	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	  	<link rel="shortcut icon" href="http://rollercoaster.sarkware.com/favicon.png" type="image/x-icon">
	
		<title>Update is in Progress - RollerCoaster</title>
		
		<style type="text/css">
			
			html,
			body {
				width: 100%;
				height: 100%;
				padding: 0px;
				margin: 0px;
				background: #252525;
				font-family: "Lucida Grande",Verdana,Arial,Helvetica,sans-serif;
			}
			
			* {				
				-webkit-box-sizing: border-box;
				   -moz-box-sizing: border-box;
					-ms-box-sizing: border-box;
					 -o-box-sizing: border-box;
					    box-sizing: border-box;
			}
			
			.rc-updater-container {
				top: 50%;
				color: #ccc;
				width: 640px;
				padding: 50px;
    			margin: 0 auto;
    			position: relative;
    			background: rgba(255,255,255,.2);    			
    			border: solid 10px rgba(0,0,0,.6);  
    			-webkit-transform: translateY(-50%);
    			   -moz-transform: translateY(-50%);
    			    -ms-transform: translateY(-50%);
    			     -o-transform: translateY(-50%);
    			        transform: translateY(-50%);
    			-webkit-box-shadow: 0px 0px 15px #000;
    			   -moz-box-shadow: 0px 0px 15px #000;
    			    -ms-box-shadow: 0px 0px 15px #000;
    			     -o-box-shadow: 0px 0px 15px #000;
    			        box-shadow: 0px 0px 15px #000;
			}
			
			.rc-updater-container h1 {
				margin-top: 0px;
				font-size: 23px;
				padding-top: 20px;
			    border-top: solid 1px #fff;
			}
			
			.rc-updater-container a {
				color: #ccc;
			}
			
			.rc-updater-header {
			  	width: 100%;
				display: table;
			}
			
			.rc-updater-header img {
				float: left;
			}
		 
			.cssload-container {
				float: left;
				width: 150px;
				height: 150px;
			}
			
			.cssload-container ul li {
				list-style: none;
			}
			
			.cssload-flex-container {
				display: flex;
					display: -o-flex;
					display: -ms-flex;
					display: -webkit-flex;
					display: -moz-flex;
				flex-direction: row;
					-o-flex-direction: row;
					-ms-flex-direction: row;
					-webkit-flex-direction: row;
					-moz-flex-direction: row;
				flex-wrap: wrap;
					-o-flex-wrap: wrap;
					-ms-flex-wrap: wrap;
					-webkit-flex-wrap: wrap;
					-moz-flex-wrap: wrap;
				justify-content: space-around;
			}
			.cssload-flex-container li {
				width: 97px;
				height: 97px;
				padding: 10px;			
				margin: 0px 20px;
				position: relative;
				text-align: center;
			}
			
			.cssload-loading, .cssload-loading:after, .cssload-loading:before {
				display: inline-block;
				position: relative;
				width: 5px;
				height: 49px;
				background: #087D79;
				margin-top: 5px;
				border-radius: 975px;
					-o-border-radius: 975px;
					-ms-border-radius: 975px;
					-webkit-border-radius: 975px;
					-moz-border-radius: 975px;
				animation: cssload-upDown2 1.15s ease infinite;
					-o-animation: cssload-upDown2 1.15s ease infinite;
					-ms-animation: cssload-upDown2 1.15s ease infinite;
					-webkit-animation: cssload-upDown2 1.15s ease infinite;
					-moz-animation: cssload-upDown2 1.15s ease infinite;
				animation-direction: alternate;
					-o-animation-direction: alternate;
					-ms-animation-direction: alternate;
					-webkit-animation-direction: alternate;
					-moz-animation-direction: alternate;
				animation-delay: 0.29s;
					-o-animation-delay: 0.29s;
					-ms-animation-delay: 0.29s;
					-webkit-animation-delay: 0.29s;
					-moz-animation-delay: 0.29s;
			}
			.cssload-loading:after, .cssload-loading:before {
				position: absolute;
				content: '';
				animation: cssload-upDown 1.15s ease infinite;
					-o-animation: cssload-upDown 1.15s ease infinite;
					-ms-animation: cssload-upDown 1.15s ease infinite;
					-webkit-animation: cssload-upDown 1.15s ease infinite;
					-moz-animation: cssload-upDown 1.15s ease infinite;
				animation-direction: alternate;
					-o-animation-direction: alternate;
					-ms-animation-direction: alternate;
					-webkit-animation-direction: alternate;
					-moz-animation-direction: alternate;
			}
			.cssload-loading:before {
				left: -10px;
			}
			.cssload-loading:after {
				left: 10px;
				animation-delay: 0.58s;
					-o-animation-delay: 0.58s;
					-ms-animation-delay: 0.58s;
					-webkit-animation-delay: 0.58s;
					-moz-animation-delay: 0.58s;
			}
			
			
			
			
			@keyframes cssload-upDown {
				from {
					transform: translateY(19px);
				}
				to {
					transform: translateY(-19px);
				}
			}
			
			@-o-keyframes cssload-upDown {
				from {
					-o-transform: translateY(19px);
				}
				to {
					-o-transform: translateY(-19px);
				}
			}
			
			@-ms-keyframes cssload-upDown {
				from {
					-ms-transform: translateY(19px);
				}
				to {
					-ms-transform: translateY(-19px);
				}
			}
			
			@-webkit-keyframes cssload-upDown {
				from {
					-webkit-transform: translateY(19px);
				}
				to {
					-webkit-transform: translateY(-19px);
				}
			}
			
			@-moz-keyframes cssload-upDown {
				from {
					-moz-transform: translateY(19px);
				}
				to {
					-moz-transform: translateY(-19px);
				}
			}
			
			@keyframes cssload-upDown2 {
				from {
					transform: translateY(29px);
				}
				to {
					transform: translateY(-19px);
				}
			}
			
			@-o-keyframes cssload-upDown2 {
				from {
					-o-transform: translateY(29px);
				}
				to {
					-o-transform: translateY(-19px);
				}
			}
			
			@-ms-keyframes cssload-upDown2 {
				from {
					-ms-transform: translateY(29px);
				}
				to {
					-ms-transform: translateY(-19px);
				}
			}
			
			@-webkit-keyframes cssload-upDown2 {
				from {
					-webkit-transform: translateY(29px);
				}
				to {
					-webkit-transform: translateY(-19px);
				}
			}
			
			@-moz-keyframes cssload-upDown2 {
				from {
					-moz-transform: translateY(29px);
				}
				to {
					-moz-transform: translateY(-19px);
				}
			}
						
		</style>		
	</head>
	
	<body>
		<div class="rc-updater-container">
			<div class="rc-updater-header">
				<img src="http://rollercoaster.sarkware.com/rc-logo.png" alt="RollerCoaster" />
				<div class="cssload-container">
					<ul class="cssload-flex-container">
						<li>
							<span class="cssload-loading"></span>
						</li>
					</ul>
				</div>
			</div>			
			<h1>Relax - RollerCoaster is being updated</h1>
			<p>from <strong>V1.0</strong> to <strong>V1.1</strong></p>
			<p>Will be restored within a moment, to know more about this update please visit this <a href="http://rollercoaster.sarkware.com/release.txt" title="RollerCoaster release note" target="_blank">link</a>.</p>
		</div>
	</body>

</head>