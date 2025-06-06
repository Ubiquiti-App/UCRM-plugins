<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Invoice CSV export</title>
        <link rel="stylesheet" href="<?php echo rtrim(htmlspecialchars($ucrmPublicUrl, ENT_QUOTES), '/'); ?>/assets/fonts/lato/lato.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
        <link rel="stylesheet" href="public/main.css">
    </head>
    <body>
        <div id="header">
            <h1>Invoice CSV export</h1>
        </div>
        <div id="content" class="container container-fluid ml-0 mr-0">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="export-form" method="post">
                                <div class="form-row align-items-end">
                                    <div class="col-3">
                                        <label class="mb-0" for="frm-organization"><small>Organization:</small></label>
                                        <select name="organization" id="frm-organization" class="form-control form-control-sm">
                                            <?php
                                                foreach ($organizations as $organization) {
                                                    printf(
                                                        '<option value="%d">%s</option>',
                                                        $organization['id'],
                                                        htmlspecialchars($organization['name'], ENT_QUOTES)
                                                    );
                                                }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="col-3">
                                        <label class="mb-0" for="frm-since"><small>Since:</small></label>
                                        <input type="date" name="since" id="frm-since" placeholder="YYYY-MM-DD" class="form-control form-control-sm">
                                    </div>

                                    <div class="col-3">
                                        <label class="mb-0" for="frm-until"><small>Until:</small></label>
                                        <input type="date" name="until" id="frm-until" placeholder="YYYY-MM-DD" class="form-control form-control-sm">
                                    </div>

                                    <div class="col-auto ml-auto">
                                        <button type="submit" class="btn btn-primary btn-sm pl-4 pr-4">Export</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
