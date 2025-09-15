<!DOCTYPE html>
<html>
<head>
    <title>e-Library Check-out, success!</title>
</head>
<body>
    <div style="background-color: #f5f5f5; padding: 20px;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px;">
            <p>Hello {{ $data['name'] }},</p>
            <br>
                <p>Your Transaction code is: </p>
                <h1>{{ $data['transaction_code'] }}</h1>
                <p>This transaction code will serve as your reference for this specific circulation.</p>
                <br>
            <br>
            <p>Best regards,</p>
            <p>Admin</p>
            <p>e-Library</p>
        </div>
    </div>
</body>
</html>
