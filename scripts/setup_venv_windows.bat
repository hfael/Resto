@echo off
REM Crée un venv et installe les dépendances sur Windows
python -m venv .venv
.venv\Scripts\pip.exe install --upgrade pip
.venv\Scripts\pip.exe install -r requirements.txt

echo venv créé dans .venv. Pour activer: .venv\Scripts\activate
echo Pour lancer les tests: .venv\Scripts\python -m pytest -q