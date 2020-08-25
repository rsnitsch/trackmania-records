# Building

python setup.py sdist
python setup.py bdist_wheel

# Uploading

twine upload dist/*
twine upload --repository testpypi dist/*

# Testing

pip install upload-tm-records
pip install --index-url https://test.pypi.org/simple/ --extra-index-url https://pypi.org/simple upload-tm-records
