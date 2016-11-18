import os
import os.path as op
from flask import Flask, url_for, redirect, render_template, request
from flask_sqlalchemy import SQLAlchemy

import flask_admin as admin
from flask_admin.contrib import sqla
from flask_admin import helpers as admin_helpers

from flask_security import Security, SQLAlchemyUserDatastore, UserMixin, RoleMixin, current_user

import random
import string
import sendgrid

app = Flask(__name__)
app.config.from_pyfile('config.py')

db = SQLAlchemy(app)

sg = sendgrid.SendGridAPIClient(apikey=app.config['SENDGRID_API_KEY'])


roles_users = db.Table('roles_users', db.Column('user_id', db.Integer(), db.ForeignKey('user.id')), db.Column('role_id', db.Integer(), db.ForeignKey('role.id')))


class Role(db.Model, RoleMixin):
    id = db.Column(db.Integer(), primary_key=True)
    name = db.Column(db.String(80), unique=True)
    description = db.Column(db.String(255))


class User(db.Model, UserMixin):
    id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(255), unique=True)
    password = db.Column(db.String(255))
    active = db.Column(db.Boolean())
    confirmed_at = db.Column(db.DateTime())
    roles = db.relationship('Role', secondary=roles_users,
                            backref=db.backref('users', lazy='dynamic'))


# Create models
class Member(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100))
    email = db.Column(db.String(120), unique=True)
    active = db.Column(db.Boolean)
    hash = db.Column(db.String(10), unique=True)

    # Required for administrative interface. For python 3 please use __str__ instead.
    def __unicode__(self):
        return self.email


# Customized User model admin
class MemberAdmin(sqla.ModelView):
    # Visible columns in the list view
    column_exclude_list = ['hash']

    form_columns = ['name', 'email', 'active']

    def _handle_view(self, name, **kwargs):
        """
        Override builtin _handle_view in order to redirect users when a view is not accessible.
        """
        if not current_user.is_authenticated():
            return redirect(url_for('security.login', next=request.url))

    def on_model_change(self, form, model, is_created):
        if (is_created):
            model.hash = ''.join(random.SystemRandom().choice(string.ascii_lowercase + string.digits) for _ in range(10))
            content = Content("text/html", render_template('email/activation.html', url=url_for('activate', hash=model.hash, _external=True), member=model))
            message = Mail(Email('kamil@szewczyk.org', 'Kamil Szewczyk'), 'Zaproszenie do losowania', Email(model.email, model.name), content)
            message.set_template_id(app.config['SENDGRID_TEMPLATE_ID'])
            try:
                response = sg.client.mail.send.post(request_body=message.get())
            except urllib.HTTPError as e:
                print(e.read())
                exit()


# Setup Flask-Security
user_datastore = SQLAlchemyUserDatastore(db, User, Role)
security = Security(app, user_datastore)

# Create admin
admin = admin.Admin(app, name='Prezenty', template_mode='bootstrap3')
# Add views
admin.add_view(MemberAdmin(Member, db.session))


# define a context processor for merging flask-admin's template context into the
# flask-security views.
@security.context_processor
def security_context_processor():
    return dict(
        admin_base_template=admin.base_template,
        admin_view=admin.index_view,
        h=admin_helpers,
    )


@app.route("/")
def index():
    return render_template('index.html')


@app.route("/activate/<hash>")
def activate(hash):
    member = Member.query.filter_by(hash=hash).first_or_404()
    member.active = True
    db.session.commit()
    return render_template('activate.html', member=member)


def build_sample_db():
    db.drop_all()
    db.create_all()
    user_datastore.create_user(email=app.config['USER_EMAIL'], password=app.config['USER_PASS'])
    db.session.commit()


def draw():
    # get all members and pick a person
    members = Member.query.all()
    picked = []
    for member in members:
        case = list(members)
        # remove current member from list
        case.remove(member)
        # remove already picked members
        for pick in picked:
            if pick in case:
                case.remove(pick)
        # try picking at random. In cases where last person to pick is also last person not picked rerun the draw
        try:
            member.pick = random.choice(case)

            picked.append(member.pick)
            print(member.email, ' -> ', member.pick.email)
        except IndexError:
            draw()

if __name__ == "__main__":
    # Build a sample db on the fly, if one does not exist yet.
    app_dir = op.realpath(os.path.dirname(__file__))
    database_path = op.join(app_dir, app.config['DATABASE_FILE'])
    if not os.path.exists(database_path):
        build_sample_db()

    # app.debug = True
    app.run()
