const AlunoModel = require('../models/AlunoModel');

const AlunoController = {
  loginForm(req, res) {
    res.render('login_aluno', { erro: null });
  },

  async login(req, res) {
    const { id_oficial, email } = req.body;
    if (!id_oficial || !email) return res.render('login_aluno', { erro: 'Preencha todos os campos.' });
    const aluno = await AlunoModel.buscarPorIdOficialEEmail(id_oficial, email);
    if (!aluno) return res.render('login_aluno', { erro: 'ID oficial ou email incorretos.' });
    req.session.aluno = { id: aluno.id, nome: aluno.nome, id_oficial: aluno.id_oficial };
    res.redirect('/aluno/painel');
  },

  async painel(req, res) {
    const aluno = await AlunoModel.buscarPorId(req.session.aluno.id);
    res.render('painel_aluno', { aluno });
  },

  logout(req, res) {
    req.session.destroy();
    res.redirect('/login/aluno');
  },
};

module.exports = AlunoController;
