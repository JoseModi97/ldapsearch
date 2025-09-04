<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search LDAP Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding-top: 56px;
        }
        .container {
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">LDAP Browser</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="search.php">Search</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="mb-4">Search for a User</h1>
        <form id="search-form" class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="cn-search" class="visually-hidden">Search by CN</label>
                <input type="text" class="form-control" id="cn-search" placeholder="Enter CN to search">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <div id="search-results" class="mt-4">
            <!-- Search results will be displayed here -->
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#search-form').on('submit', function(e) {
                e.preventDefault();
                const cn = $('#cn-search').val();
                if (!cn) {
                    alert('Please enter a CN to search for.');
                    return;
                }

                $('#search-results').html('<p class="text-muted">Searching...</p>');

                $.ajax({
                    url: 'browse.php',
                    type: 'POST',
                    data: { search_cn: cn },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            $('#search-results').html('<div class="alert alert-danger">Error: ' + response.error + '</div>');
                            return;
                        }
                        if (response.results && response.results.length > 0) {
                            let html = '<table class="table table-bordered"><thead><tr><th>DN</th><th>CN</th><th>Email</th><th>Display Name</th><th>Password</th></tr></thead><tbody>';
                            response.results.forEach(function(user) {
                                html += `<tr><td>${user.dn}</td><td>${user.cn}</td><td>${user.mail}</td><td>${user.displayName}</td><td>${user.userPassword}</td></tr>`;
                            });
                            html += '</tbody></table>';
                            $('#search-results').html(html);
                        } else {
                            $('#search-results').html('<p class="text-muted">No users found.</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#search-results').html('<div class="alert alert-danger">Failed to perform search. Status: ' + status + ', Error: ' + error + '</div>');
                    }
                });
            });
        });
    </script>
</body>
</html>
