<!DOCTYPE html>
<html>
<head>
    <title>e-Library Registration, success!</title>
</head>
<body>
    <div style="background-color: #f5f5f5; padding: 20px;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px;">
            <p>Hello {{ $data['fullname'] }},</p>
            <br>
                <p>Your Library code is: </p>
                <h1>{{ $data['library_code'] }}</h1>
                <p>This library code will serve as your library unique ID.</p>
                @if($data['type'] == "admin")
                    <br>
                    <p>Your default password is:</p>
                    <h3>{{ $data['password'] }}</h3>
                    <p>Please change your password once you login.</p>
                @endif
            <br>
            <p>Best regards,</p>
            <p>Admin</p>
            <p>e-Library</p>
        </div>
    </div>
</body>
</html>
