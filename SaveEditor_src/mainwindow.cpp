
#include <QStandardPaths>
#include <QDebug>
#include <QFile>
#include <QFileDialog>
#include <QMessageBox>
#include <QDateTime>
#include <QJsonDocument>
#include <QJsonObject>
#include <QJsonArray>
#include <QProcess>

#include "mainwindow.h"
#include "ui_mainwindow.h"

MainWindow::MainWindow(QWidget *parent) :
    QMainWindow(parent),
    ui(new Ui::MainWindow)
{
    //m_windowSize.setWidth(-1);
    //m_windowSize.setHeight(-1);
    //m_windowPos.setX(-1);
    //m_windowPos.setY(-1);

    ui->setupUi(this);

    QString localAppDataDir = QStandardPaths::standardLocations(QStandardPaths::GenericConfigLocation).at(0);
    QString defaultPath = localAppDataDir + "/IslandsofInsight/Saved/SaveGames/OfflineSavegame.sav";
    this->UpdateSaveFilePath(defaultPath);

    QDir::setCurrent(qApp->applicationDirPath());

    QString uesavePath = qApp->applicationDirPath() + "\\uesave\\uesave.exe";
    if(!QFile::exists(uesavePath)){
        QMessageBox msgBox;
        msgBox.setIcon(QMessageBox::Warning);
        msgBox.setText("Couldn't find uesave/uesave.exe\nPlease reinstall this program");
        msgBox.exec();
        exit(1);
    }

    m_tempDir = qApp->applicationDirPath() + "\\temp";
    QDir tempDir(m_tempDir);
    if(!tempDir.exists()){
        tempDir.mkdir(m_tempDir);
    }
    m_tempPath = m_tempDir + "\\temp.json";

    connect(ui->statusBar, &QStatusBar::messageChanged, this, &cloneStatusBarMessage);
    ui->statusBar->hide();

    //isInitialized = true;
}

MainWindow::~MainWindow()
{
    delete ui;
}

//QStringList MainWindow::LoadConfig()
//{
//    static QString configPath = "config.txt";
//    if(!QFile::exists(configPath)){
//        return QStringList();
//    }
//    QFile inputFile(configPath);
//    if(!inputFile.open(QIODevice::ReadOnly)){
//        return QStringList();
//    }
//    QTextStream in(&inputFile);
//    QStringList result;
//    while (!in.atEnd()){
//        QString line = in.readLine();
//        result.append(line);
//    }
//    inputFile.close();
//    return result;
//}

//void MainWindow::SaveConfig(const QStringList& config)
//{
//
//}

//void MainWindow::resizeEvent(QResizeEvent* event)
//{
//    QMainWindow::resizeEvent(event);
//    if(!isInitialized){
//        return;
//    }
//    qDebug() << "Resized to " << event->size().width() << "x" << event->size().height();
//    m_windowSize = event->size();
//}

//void MainWindow::moveEvent(QMoveEvent* event)
//{
//    QMainWindow::moveEvent(event);
//    if(!isInitialized){
//        return;
//    }
//    qDebug() << "Moved to " << event->pos().x() << "x" << event->pos().y();
//    m_windowPos = event->pos();
//}

void MainWindow::on_buttonSelectSavePath_clicked()
{
    QFileInfo fi(m_savePath);

    QFileDialog dialog(this);
    dialog.setFileMode(QFileDialog::ExistingFile);
    dialog.setNameFilter(tr("IOI Save File (*.sav)"));
    dialog.setDirectory(fi.dir());
    if (dialog.exec()){
        QString newPath = dialog.selectedFiles().at(0);
        UpdateSaveFilePath(newPath);
    }
}

void MainWindow::on_buttonApply_clicked()
{
    QStringList args;

    if(ui->checkResetCampaign->isChecked())         { args.append("resetCampaignProgress");  }
    if(ui->checkResetTempleArmillaries->isChecked()){ args.append("resetTempleArmillaries"); }
    if(ui->checkResetVanillaClusters->isChecked())  { args.append("resetVanillaClusters");   }
    if(ui->checkResetLostgridsCluster->isChecked()) { args.append("resetLostgridsCluster");  }
    if(ui->checkResetHubProgress->isChecked())      { args.append("resetHubProgress");       }
    if(ui->checkResetOnlineFlorbs->isChecked())     { args.append("resetOnlineFlorbs");      }
    if(ui->checkResetNonPlatFlorbs->isChecked())    { args.append("resetNonPlatFlorbs");     }
    if(ui->checkResetNonPlatGlides->isChecked())    { args.append("resetNonPlatGlides");     }

    if(ui->checkFixEncyclopedia->isChecked())       { args.append("fixMissingEncyclopedia"); }
    if(ui->checkFixExcessMirabilis->isChecked())    { args.append("fixExcessMirabilis");     }
    if(ui->checkHidePing->isChecked())              { args.append("hidePing");               }
    if(ui->checkRefundFiveMarkers->isChecked())     { args.append("refundFiveMarkers");      }
    if(ui->checkResetDailyQuests->isChecked())      { args.append("resetDailyQuests");       }
    if(ui->checkFixUnsolvedMonoliths->isChecked())  { args.append("fixUnsolvedMonoliths");   }
    if(ui->checkFixNegativeSparks->isChecked())     { args.append("fixNegativeSparks");      }

    if(args.empty()){
        QMessageBox msgBox;
        msgBox.setIcon(QMessageBox::Information);
        msgBox.setText(QString() + "Nothing selected :)");
        msgBox.exec();
        return;
    }

    QDateTime dt = QDateTime::currentDateTime();
    //QString formattedTime = dt.toString("dd-MM-yyyy_hh-mm_sszzz");
    QString formattedTime = dt.toString("yyyy-MM-dd_hhmmss");
    QString backupName = QString() + "OfflineSavegame_" + formattedTime + ".sav";
    QString backupPath = qApp->applicationDirPath() + "\\" + backupName;


    bool isCopyOk = QFile::copy(m_savePath, backupPath);
    if(!isCopyOk){
        QMessageBox msgBox;
        msgBox.setIcon(QMessageBox::Warning);
        msgBox.setText(QString() + "Couldn't create a backup in this folder!\n\nAborting changes!");
        msgBox.exec();
        return;
    }

    QProcess decodeProcess;
    QString finalCmd = QString() + "\"php/php.exe\" saveeditor.php \"" + m_savePath + "\" \"" + m_tempPath + "\" \"" + m_savePath + "\" " + args.join(" ");
    qDebug() << finalCmd;

    decodeProcess.start(finalCmd);
    decodeProcess.waitForFinished(-1);

    QMessageBox msgBox;
    msgBox.setIcon(QMessageBox::Information);
    msgBox.setText(QString() + "Looks good!\nRemember to disable Steam Cloud temporarily!\n\nCreated backup:\n" + backupName);
    msgBox.exec();
}

void MainWindow::on_buttonClose_clicked()
{
    //QApplication::closeAllWindows();
    qApp->exit();
}

void MainWindow::UpdateSaveFilePath(const QString& s)
{
    if(!QFile::exists(s)){
        m_savePath = "";
        ui->lineSavePath->setText("");
        return;
    }
    m_savePath = s;
    ui->lineSavePath->setText(m_savePath);
}

void MainWindow::on_lineSavePath_textEdited(const QString &arg1)
{
    m_savePath = arg1;
}

void MainWindow::cloneStatusBarMessage(const QString& message)
{
    // Instead of designing my own hover on/off event handles and stuff
    // I just let the standard status bar handle the changes automatically.
    // Then I duplicate whatever status bar displays and hide the status bar itself.
    // Simple, works, good enough.
    // The purpose is to have a large multi-line text box, btw.

    //ui->lineSavePath->setText(message);
    //QString a = message.split("\\n").join("\n");
    QString a = message.split("|").join("\n").trimmed();
    ui->textStatus->setPlainText(a);
}
