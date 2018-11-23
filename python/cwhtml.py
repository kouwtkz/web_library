# coding: utf-8
"""html用のオブジェクトLibraryです"""
import io
import sys
import urllib.parse as parse
from os import environ

if sys.stdout.encoding != 'utf8':
    sys.stdin = io.TextIOWrapper(sys.stdin.buffer, encoding='utf-8')
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8')

"""生成されたHTMLはこちらでストックされる、配列連結メソッドで仕上げ"""
HTML_BUF = []
"""閉じるときの出力フラグ、自動的に有効"""
PRINT_FLAG = True
CALL_STATUS = False

# 予め決めたグローバル変数ゾーン
HTTP = 'http://'
HTTPS = 'https://'
TITLE = "Unfined"
TABSTR = "    "

"""URL周りの変数ゾーン"""
HTTP_HOST = environ['HTTP_HOST'] if 'HTTP_HOST' in environ else ''
REQUEST_SCHEME = environ['REQUEST_SCHEME'] if 'REQUEST_SCHEME' in environ else ''
REQUEST_URI = environ['REQUEST_URI'] if 'REQUEST_URI' in environ else ''
SCRIPT_NAME = environ['SCRIPT_NAME'] if 'SCRIPT_NAME' in environ else ''

DOMAIN = (REQUEST_SCHEME + '://' + HTTP_HOST) if REQUEST_SCHEME != '' else ''
URL = DOMAIN + REQUEST_URI
SCRIPT_URL = DOMAIN + SCRIPT_NAME
REFERER_URL = environ['HTTP_REFERER'] if 'HTTP_REFERER' in environ else ''

PARSE_URL = parse.urlparse(URL)
PATH_LIST = str(PARSE_URL.path).split('/')
REQUEST_METHOD = environ['REQUEST_METHOD'] if 'REQUEST_METHOD' in environ else ''

def cgitb_enable():
    """エラーモードを有効にする"""
    import cgitb
    cgitb.enable()

def enable(*print_flag):
    """自身を呼び出す、引数はPRINT_FLAGの設定"""
    global CALL_STATUS, PRINT_FLAG
    if bool(print_flag):
        if isinstance(print_flag[0], bool):
            PRINT_FLAG = print_flag[0]
    if PRINT_FLAG and not CALL_STATUS:
        print("Content-Type: text/html; charset=UTF-8\r\n")
    CALL_STATUS = True

def get_html():
    """バッファされたHTMLを統合して書き出す"""
    return ''.join(HTML_BUF)

def get_post():
    """Postの取得を有効にする"""
    from cgi import FieldStorage
    return FieldStorage() if (REQUEST_METHOD == 'POST') or (PARSE_URL.query != '') else {}

def set_elem(dic):
    """連想配列(辞書)からタグ内の変数を設定する"""
    elem = ""
    for _v in dic.items():
        elem += " " + str(_v[0]) + "='" + str(_v[1]) + "'"
    return elem

def itag(name, *args):
    """タグの生成、1.タグ名、2.辞書、3.本文str、4.改行フラグ(Trueで改行)"""
    body = ''
    elem = ''
    _br = ''
    if bool(args):
        i = 0
        a_len = len(args)
        if a_len > i:
            if isinstance(args[i], dict):
                elem = set_elem(args[i])
                i += 1
        if a_len > i:
            if isinstance(args[i], str):
                body = args[i]
                i += 1
        if a_len > i:
            if isinstance(args[i], bool):
                _br = '\n' if args[i] else ''
    return '<' + name + elem + '>' + body + '</' + name + '>' + _br

def attr(name, *args):
    """属性タグの生成、1.タグ名、2.辞書、3.改行フラグ(Trueで改行)"""
    _br = ''
    if bool(args):
        a_len = len(args)
        elem = ''
        i = 0
        if isinstance(args[0], dict):
            elem = set_elem(args[0])
            i += 1
        if a_len > i:
            if isinstance(args[i], bool):
                _br = '\n' if args[i] else ''
    return '<'+ str(name) + elem + ' />' + _br

def attr_input(_type, _name, *args):
    """inputタグの生成、1.タイプ、2.名前、3.辞書、4.改行フラグ(Trueで改行)"""
    br_flag = False
    dic = {'type': _type}
    if _name != '':
        dic.update({'name': _name})
    if bool(args):
        a_len = len(args)
        if isinstance(args[0], dict):
            dic.update(args[0])
        if a_len > 1:
            if isinstance(args[1], bool):
                br_flag = args[1]
    return attr('input', dic, br_flag)

def meta(name, content):
    """meta属性の中でnameとcontentの追加のみ"""
    return attr('meta', {'name': str(name), 'content': str(content)})

def read_txt(file_path):
    _encoding = "utf-8_sig"
    _f = open(file_path, 'r', encoding=_encoding)
    _data = _f.read()
    _f.close()
    return _data

def has_key(key, dic):
    """CGIから入っているかどうかの確認"""
    if isinstance(dic[0], dict):
        check = dic[0]
        return key in check
    return False

def pre(text):
    """タグをそのまま表示するためのメソッド"""
    return text.replace('<', '&lt;').replace('>', '&gt;')

class Base:
    """全ての元となる親オブジェクト"""
    name = "base"
    object_flag = False
    """属性の定義"""
    elem = {}
    e_ofs = 0
    """インデントの定義"""
    tabstr = ""
    """前回のオブジェクト"""
    super_obj = None
    """出力時に改行するかどうか"""
    not_crlf = False

    def __init__(self, *args):
        # メンバ変数の初期化処理
        self.tabstr = TABSTR
        self.e_ofs = 0
        self.indent = 0
        self.elem = {}
        if not self.object_flag:
            self.startdefs(args)
            self.elemdefs(args)
            self.out("<" + self.name + set_elem(self.elem) + ">")
            self.indent += 1
            self.object_flag = True
            self.constdefs(args)
    def __del__(self):
        self.close()
    def __endtag(self):
        if self.object_flag:
            self.indent -= (1 if self.indent > 0 else self.indent)
            self.out("</" + self.name + ">" + ('\n' if self.not_crlf else ''))
            self.object_flag = False
    def close(self):
        """明示的に閉じるときのメソッド"""
        self.closedefs()
        self.__endtag()
        self.enddefs()
        return self.super_obj
    def startdefs(self, *args):
        """継承用、<base>タグの始まりより前に実行する関数"""
        pass
    def elemdefs(self, *args):
        """継承用、属性を定義する、有無を継承で区別"""
        if bool(args[0]):
            a_len = len(args[0])
            if a_len > self.e_ofs:
                if isinstance(args[0][self.e_ofs], dict):
                    self.elem.update(args[0][self.e_ofs])
                    self.e_ofs += 1
            if a_len > self.e_ofs:
                get_obj = args[0][self.e_ofs]
                if isinstance(get_obj, int):
                    self.indent = get_obj
                else:
                    self.super_obj = get_obj
                    self.indent = get_obj.indent
    def constdefs(self, *args):
        """継承用、<base>タグよりも後に実行する関数"""
        pass
    def closedefs(self, *args):
        """継承用、</base>タグの手前に実行する関数"""
        pass
    def enddefs(self, *args):
        """継承用、</base>タグの後に実行する関数"""
        pass
    def __enter__(self):
        return self
    def __exit__(self, types, value, traceback):
        self.close()
    def out(self, text):
        """インデントの状態に合わせて出力、配列に格納される"""
        out_indent = ('' if (self.object_flag and self.not_crlf) \
        else (self.tabstr * self.indent))
        out_str = out_indent + str(text).replace('\n', '\n' + out_indent) \
            + ('' if self.not_crlf else '\n')
        HTML_BUF.append(out_str)
    def load_css(self, path):
        """外部スタイルシートの設定"""
        self.out('<link rel="stylesheet" href="' + path + '" type="text/css" />')
    def load_scr(self, path):
        """外部スクリプトの設定"""
        self.out('<script src="' + str(path) + '"></script>')

class Html(Base):
    """htmlタグのオブジェクト"""
    name = "html"
    def startdefs(self, *args):
        global CALL_STATUS
        if not CALL_STATUS:
            enable()
        HTML_BUF.clear()        # 初期化処理
        self.out("<!DOCTYPE html>")
    def enddefs(self, *args):
        global PRINT_FLAG, CALL_STATUS
        if PRINT_FLAG and CALL_STATUS:
            print(get_html())
            CALL_STATUS = False

class Head(Base):
    """headタグのオブジェクト"""
    __title_flag = False
    title = ""
    name = "head"
    def startdefs(self, *args):
        self.title = TITLE
        if bool(args[0]):
            title = args[0][0]
            if isinstance(title, str):
                if title != '':
                    self.title = title
                self.__title_flag = True
                self.e_ofs += 1
    def constdefs(self, *args):
        self.out('<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />')
        if self.__title_flag:
            self.set_title()
    def set_title(self, *args):
        """タイトル名を設定"""
        if bool(args):
            _get = args[0]
            if isinstance(_get, str):
                if _get != "":
                    self.title = args[0]
        self.out("<title>" + self.title + "</title>")
    def lcss(self, path):
        """簡略CSSの貼り付け"""
        self.load_css(path)

class Body(Base):
    """Bodyタグ、混乱を防ぐのが主です"""
    name = "body"

class Tag(Base):
    """独自にタグで作りたい時"""
    def startdefs(self, *args):
        a_len = len(args[0])
        if bool(args[0]):
            self.name = str(args[0][0])
            self.e_ofs += 1
            if a_len > 1:
                if isinstance(args[0][1], bool):
                    self.not_crlf = args[0][1]
                    self.e_ofs += 1

class Form(Base):
    """コンストラクタの第一引数:action、第二引数:method"""
    name = "form"
    def startdefs(self, *args):
        a_len = len(args[0])
        if bool(args[self.e_ofs]):
            if isinstance(args[0][self.e_ofs], str):
                self.elem.update({'action': args[0][self.e_ofs]})
                self.e_ofs += 1
            if a_len > self.e_ofs:
                if isinstance(args[0][self.e_ofs], str):
                    self.elem.update({'method': args[0][self.e_ofs]})
                    self.e_ofs += 1

class Style(Base):
    """スタイルシート専用"""
    name = 'style'
    def startdefs(self, *args):
        self.elem.update({'type': 'text/css'})

class Script(Base):
    """Script専用"""
    name = 'script'
