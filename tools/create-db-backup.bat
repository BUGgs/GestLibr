@echo off
rem Compression des fichiers + upload vers FTP
echo ----------------------------------------------------------
echo Compression en cours... Veuillez patientez...
echo ----------------------------------------------------------
del sauve-db.sql
del sauve-db.zip
..\..\..\mysql\bin\mysqldump --host=localhost --user=root gestlibr --add-drop-table --complete-insert > sauve-db.sql
cd 7-Zip
7z a -tzip ..\sauve-db.zip ..\sauve-db.sql
cd ..
echo ----------------------------------------------------------
echo Envois sur le serveur en cours...
echo ----------------------------------------------------------
curl -T sauve-db.zip -u librairieduchene-com:rd8207 ftp://ftp.wiroo.com/www/gestlibr-sauve/
echo ----------------------------------------------------------
echo FIN de la sauvegarde
echo ----------------------------------------------------------
pause