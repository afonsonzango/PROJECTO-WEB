function requireSecretaria(req, res, next) {
  if (req.session && req.session.secretaria) return next();
  res.redirect('/login/secretaria');
}

function requireDiretor(req, res, next) {
  if (req.session && req.session.diretor) return next();
  res.redirect('/login/diretor');
}

function requireAluno(req, res, next) {
  if (req.session && req.session.aluno) return next();
  res.redirect('/login/aluno');
}

module.exports = { requireSecretaria, requireDiretor, requireAluno };
