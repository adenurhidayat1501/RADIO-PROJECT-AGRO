@echo off
title Build Radio Agro Desktop Player
echo ==============================================================================
echo Building Radio Agro Native Desktop Player (RadioAgroPlayer.exe)
echo ==============================================================================

set CSC_PATH=C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe

if not exist "%CSC_PATH%" (
    set CSC_PATH=C:\Windows\Microsoft.NET\Framework\v4.0.30319\csc.exe
)

if not exist "%CSC_PATH%" (
    echo [ERROR] Microsoft .NET Framework C# Compiler (csc.exe) not found!
    pause
    exit /b 1
)

echo Using C# Compiler: %CSC_PATH%
echo Compiling src\Program.cs ...

"%CSC_PATH%" /target:winexe /optimize+ /out:"RadioAgroPlayer.exe" /r:System.dll,System.Windows.Forms.dll,System.Drawing.dll,System.Web.Extensions.dll,"C:\Windows\Microsoft.NET\Framework64\v4.0.30319\WPF\PresentationCore.dll","C:\Windows\Microsoft.NET\Framework64\v4.0.30319\WPF\WindowsBase.dll" "src\Program.cs"

if %ERRORLEVEL% equ 0 (
    echo.
    echo ==============================================================================
    echo [SUCCESS] Build succeeded! RadioAgroPlayer.exe generated successfully.
    echo ==============================================================================
) else (
    echo.
    echo [ERROR] Compilation failed with error code %ERRORLEVEL%
)

echo.
pause
