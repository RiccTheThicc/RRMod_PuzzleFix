#ifndef MAINWINDOW_H
#define MAINWINDOW_H

#include <QMainWindow>

namespace Ui {
class MainWindow;
}

class MainWindow : public QMainWindow
{
        Q_OBJECT

    public:
        explicit MainWindow(QWidget *parent = 0);
        ~MainWindow();
        bool isInitialized = false;

    private slots:
        void on_buttonSelectSavePath_clicked();
        void on_buttonApply_clicked();
        void on_buttonClose_clicked();
        void on_lineSavePath_textEdited(const QString &arg1);
        void cloneStatusBarMessage(const QString& message);
        //void resizeEvent(QResizeEvent* event);
        //void moveEvent(QMoveEvent* event);

    private:
        //QStringList LoadConfig();
        //void SaveConfig(const QStringList& config);
        void UpdateSaveFilePath(const QString& s);
        Ui::MainWindow *ui;
        QString m_savePath;
        QString m_tempDir;
        QString m_tempPath;
        //QSize m_windowSize;
        //QPoint m_windowPos;
};

#endif // MAINWINDOW_H

