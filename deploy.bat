@echo off
echo Deploying to games.firststepreading.com...
echo.

scp -P 22 -i "%USERPROFILE%\.ssh\firststep_key" -o StrictHostKeyChecking=no ^
  *.html fonts.css site-nav.js ^
  firststep@192.145.235.207:~/public_html/reading-games/

scp -r -P 22 -i "%USERPROFILE%\.ssh\firststep_key" -o StrictHostKeyChecking=no ^
  icons/ ^
  firststep@192.145.235.207:~/public_html/reading-games/

echo.
echo Done! Visit https://games.firststepreading.com
pause
