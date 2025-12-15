<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Argentina CAE - Seleccione Organizacion</title>
        <link rel="stylesheet" href="<?php echo rtrim(htmlspecialchars($ucrmPublicUrl, ENT_QUOTES), '/'); ?>/assets/fonts/lato/lato.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
        <link rel="stylesheet" href="public/main.css">
    </head>
    <body>
        <div id="header">
            <h1> Argentina CAE - Seleccione Organizacion</h1>
        </div>
        <div id="content" class="container container-fluid ml-0 mr-0">
            <div class="row mb-4">
                <div class="col-auto">
                    <div class="card">
                        <div class="card-body">
                            <form id="export-form">
                                <div class="form-row align-items-end">
                                    <div class="col-auto">
                                        <label class="mb-0" for="frm-organization"><small>Organizacion:</small></label>
                                        <select name="organization" id="frm-organization" class="form-control form-control-sm" >
                                            <?php
                                                $orgNumber = 1;
                                                foreach ($organizaciones as $organizacion) {
                                                    printf(
                                                        '<option value="%s">%s</option>',
                                                        htmlspecialchars($organizacion['id'] . ',' . $organizacion['salesPoint'] . ',' . $organizacion['activitiesStartDate'] . ',' . $orgNumber, ENT_QUOTES),
                                                        htmlspecialchars($organizacion['name'] . ' - Punto de Venta: ' . $organizacion['salesPoint'], ENT_QUOTES)
                                                    );
                                                    $orgNumber++;
                                                }
                                            ?>
                                        </select>
                                    </div>
									<div class="col-auto ml-auto">
                                        <button type="submit" class="btn btn-primary btn-sm pl-4 pr-4">Solicitar CAE</button>
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
