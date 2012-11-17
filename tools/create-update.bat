@echo off
rem Compression des fichiers + upload vers FTP
echo ----------------------------------------------------------
echo Compression en cours... Veuillez patientez...
echo ----------------------------------------------------------
del sauve.zip
cd 7-Zip
7z a -r -tzip -x!..\..\tools\sauve.zip ..\sauve.zip ..\..\*
cd ..
echo ----------------------------------------------------------
echo Envois sur le serveur en cours...
echo ----------------------------------------------------------
curl -T sauve.zip -u librairieduchene-com:rd8207 ftp://ftp.wiroo.com/www/gestlibr-sauve/
echo ----------------------------------------------------------
echo FIN de l'Upload
echo ----------------------------------------------------------
pause