ssl\bin\openssl genrsa -out UCRM.key 2048
ssl\bin\openssl req -config certificado.txt -out UCRM.request.csr -verify -key UCRM.key -new -batch


