<!DOCTYPE html>
<html lang="en">
<head>
    <title>PHP SMS Queue</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="script/css/bootstrap.min.css">

    <style>
        .error {color: red;}
        .success {color: green;}
    </style>
</head>

<body>
    <nav class="navbar navbar-inverse">
        <div class="container-fluid">
            <ul class="nav navbar-nav">
                <li class="active"><a href="#">Insert SMS Message</a></li>
                <li><a href="smsqueue.php?retrieve" target="_blank">Consume SMS Message from Queue</a></li>
                <li><a href="smsqueue.php?total" target="_blank">Total No of Message in Queue</a></li>
                <li><a href="smsqueue.php?all" target="_blank">Get All SMS Messages in Queue</a></li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <h2 class="text-center">SMS Form</h2>
        <form class="form-horizontal" method="post" action="smsqueue.php">
            <div class="form-group">
                <label class="control-label col-sm-2" for="message">Message:</label>
                <div class="col-sm-10"> 
                    <?php echo isset($_GET['messageResponse']) ? $_GET['messageResponse'] : '';?>    
                    <textarea name="message" class="form-control" placeholder="Enter message"></textarea>
                </div>
            </div>
  
            <div class="form-group">        
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" name="submit" class="btn btn-default">Submit</button>
                </div>
            </div>
        </form>
    </div>
</body>

<script src="script/js/jquery.min.js"></script>
<script src="script/js/bootstrap.min.js"></script>
</html>