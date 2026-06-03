const DiretorModel = require('../models/DiretorModel');
const AlunoModel = require('../models/AlunoModel');

const DiretorController = {
  loginForm(req, res) {
    res.render('login_diretor', { erro: null });
  },

  async login(req, res) {
    const { usuario, senha } = req.body;
    if (!usuario || !senha) return res.render('login_diretor', { erro: 'Preencha todos os campos.' });
    if (senha.length < 6) return res.render('login_diretor', { erro: 'Senha deve ter pelo menos 6 caracteres.' });
    const resultado = await DiretorModel.criarOuAutenticar(usuario, senha);
    if (!resultado) return res.render('login_diretor', { erro: 'Credenciais inválidas.' });
    req.session.diretor = { usuario: resultado.usuario };
    res.redirect('/diretor/painel');
  },

  async painel(req, res) {
    const [stats, abertas] = await Promise.all([
      AlunoModel.contagens(),
      DiretorModel.matriculasAbertas(),
    ]);
    res.render('painel_diretor', {
      stats,
      abertas,
      usuario: req.session.diretor.usuario,
      flash: req.session.flash || null,
    });
    delete req.session.flash;
  },

  async toggleMatriculas(req, res) {
    const abertas = await DiretorModel.matriculasAbertas();
    await DiretorModel.toggleMatriculas(abertas);
    req.session.flash = { tipo: 'sucesso', msg: `Matrículas agora estão ${!abertas ? 'ABERTAS' : 'FECHADAS'}.` };
    res.redirect('/diretor/painel');
  },

  logout(req, res) {
    req.session.destroy();
    res.redirect('/login/diretor');
  },
};

module.exports = DiretorController;
