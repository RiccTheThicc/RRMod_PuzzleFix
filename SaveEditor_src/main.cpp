#include "mainwindow.h"
#include <QApplication>
#include <QMessageBox>

int main(int argc, char *argv[])
{
    QApplication a(argc, argv);
    MainWindow w;
    w.show();
    w.isInitialized = true;

    //return a.exec();

    int result = a.exec();
    // Intercept app quit here to update config file before closing?
    //QMessageBox msgBox;
    //msgBox.setIcon(QMessageBox::Warning);
    //msgBox.setText("Quitting app!");
    //msgBox.exec();
    return result;
}
