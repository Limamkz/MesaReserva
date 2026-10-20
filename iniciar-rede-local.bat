@echo off
setlocal EnableExtensions
title MesaReserva - Rede Local
echo ==========================================
echo   MESARESERVA - ACESSO PELA REDE LOCAL
echo ==========================================
echo.
echo Este arquivo e opcional. Inicie Apache e MySQL pelo XAMPP Control Panel.
echo.
echo Enderecos IPv4 encontrados neste computador:
ipconfig | findstr /i /c:"IPv4 Address" /c:"Endereco IPv4"
echo.
echo Neste computador, abra: http://localhost/MesaReserva/
echo Em outros dispositivos, use http://IP-DO-COMPUTADOR/MesaReserva/
echo.
echo Se uma janela de terminal piscar mesmo sem executar este arquivo,
echo ela esta sendo iniciada por outro processo do Windows.
echo.
start "" "http://localhost/MesaReserva/"
endlocal
