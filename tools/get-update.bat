@echo off
rem Reccup de la MAJ via FTP + unzip
echo ----------------------------------------------------------
echo Reccup�ration de la mise � jour sur le serveur en cours...
echo ----------------------------------------------------------
del sauve.zip
curl -u librairieduchene-com:rd8207 ftp://ftp.wiroo.com/www/gestlibr-sauve/sauve.zip
echo ----------------------------------------------------------
echo D�compression en cours... Veuillez patientez...
echo ----------------------------------------------------------
cd 7-Zip
7z x -y -x!tools\7-Zi* -o..\..\ ..\sauve.zip
cd ..
echo ----------------------------------------------------------
echo FIN de la mise � jour
echo ----------------------------------------------------------
pause