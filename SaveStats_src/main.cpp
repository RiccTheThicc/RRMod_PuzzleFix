#include "mainwindow.h"
//#include <QApplication>
#include <QProcess>
#include <unistd.h>
#include <sys/types.h>

int main(int argc, char *argv[])
{
    //QApplication a(argc, argv);
    //MainWindow w;
    //w.show();
    //return a.exec();

    //QProcess process;
    ////decodeProcess.setArguments(args);
    ////decodeProcess.setProgram("php/php.exe phpedit.php");
    ////decodeProcess.start("php/php.exe phpedit.php");
    //
    //
    //QString finalCmd = QString() + "php/php.exe phpedit.php \"" + m_savePath + "\" \"" + m_tempPath + "\" " + args.join(" ");
    //qDebug() << finalCmd;
    //decodeProcess.start(finalCmd);
    //decodeProcess.waitForFinished(-1);
    system("savedump.bat; pause");
    //QProcess process;
    //process.start("php/php.exe savedump.php; pause");
    //process.waitForFinished(-1);
    //execl("php/php.exe savedump.php; pause", "");
    //passthru("php/php.exe savedump.php; pause", "");

    return 0;
}
