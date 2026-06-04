const express = require('express');
const multer = require('multer');
const path = require('path');
const router = express.Router();

const HomeController = require('../controllers/HomeController');
const MatriculaController = require('../controllers/MatriculaController');
const AlunoController = require('../controllers/AlunoController');
const SecretariaController = require('../controllers/SecretariaController');
const DiretorController = require('../controllers/DiretorController');
const { requireAluno, requireSecretaria, requireDiretor } = require('../middleware/auth');

const storage = multer.diskStorage({
  destination: path.join(__dirname, '../../public/uploads'),
  filename: (req, file, cb) => {
    cb(null, Date.now() + '-' + file.originalname.replace(/\s/g, '_'));
  },
});
const upload = multer({ storage, limits: { fileSize: 10 * 1024 * 1024 } });

// Home
router.get('/', HomeController.index);

// Matrícula
router.get('/fazer-matricula', MatriculaController.form);
router.post('/fazer-matricula', upload.fields([{ name: 'foto', maxCount: 1 }, { name: 'certificado', maxCount: 1 }]), MatriculaController.submit);
router.get('/status-matricula', MatriculaController.statusForm);
router.post('/status-matricula', MatriculaController.status);

// Aluno
router.get('/login/aluno', AlunoController.loginForm);
router.post('/login/aluno', AlunoController.login);
router.get('/aluno/painel', requireAluno, AlunoController.painel);
router.get('/logout/aluno', AlunoController.logout);

// Secretaria
router.get('/login/secretaria', SecretariaController.loginForm);
router.post('/login/secretaria', SecretariaController.login);
router.get('/secretaria/painel', requireSecretaria, SecretariaController.painel);
router.post('/secretaria/aprovar/:id', requireSecretaria, SecretariaController.aprovar);
router.post('/secretaria/recusar/:id', requireSecretaria, SecretariaController.recusar);
router.post('/secretaria/aceitar-reconf/:id', requireSecretaria, SecretariaController.aceitarReconf);
router.post('/secretaria/recusar-reconf/:id', requireSecretaria, SecretariaController.recusarReconf);
router.post('/secretaria/alterar-senha', requireSecretaria, SecretariaController.alterarSenha);
router.get('/logout/secretaria', SecretariaController.logout);

// Diretor
router.get('/login/diretor', DiretorController.loginForm);
router.post('/login/diretor', DiretorController.login);
router.get('/diretor/painel', requireDiretor, DiretorController.painel);
router.post('/diretor/toggle-matriculas', requireDiretor, DiretorController.toggleMatriculas);
router.get('/logout/diretor', DiretorController.logout);

module.exports = router;