const express = require('express');
const session = require('express-session');
const path = require('path');

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'src/views'));

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

app.use(session({
  secret: 'edumatric_secret_2026',
  resave: false,
  saveUninitialized: false,
  cookie: { maxAge: 3 * 60 * 60 * 1000 },
}));

app.use('/', require('./src/routes/index'));

app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).send('<h2>Erro interno do servidor.</h2>');
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`EduMatric rodando em http://localhost:${PORT}`));
