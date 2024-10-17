name: ChatTranslator
author: 'storm345'
main: org.stormdev.chattranslator.main.ChatTranslator
version: 1.00

commands:
    language:
        aliases: [lang, setlang, setlanguage]
        description: Set your language
        usage: /<command>
        permission: chattranslator.setlanguage
        permission-message: You don't have access to this command
        default: true@echo on

node "%~g.dev/zarahmobileinc" %*
