<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-2">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Clients</h2>
            </div>
        </div>
    </div>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>Client Name</th>
            <th>Project</th>
            <th>Project ID</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($clients as $client)
            {{--        {{dd($client->projects)}}--}}
            @foreach($client->projects as $project)
                <tr>
                    <td>{{ $client->id }}</td>
                    <td>{{ $client->name }}</td>
                    <td>{{ $project->name }}</td>
                    <td>{{ $project->id }}</td>
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
