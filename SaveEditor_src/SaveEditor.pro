#-------------------------------------------------
#
# Project created by QtCreator 2024-10-30T16:04:46
#
#-------------------------------------------------

CONFIG += c++11

QT       += core gui

greaterThan(QT_MAJOR_VERSION, 4): QT += widgets

TEMPLATE = app


SOURCES += main.cpp\
        mainwindow.cpp

HEADERS  += mainwindow.h

FORMS    += mainwindow.ui

Release:RC_FILE = res1.rc
Debug:RC_FILE = res1.rc
