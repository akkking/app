package com.wikia.webdriver.TestCases.ArticleCRUDTests;

import org.testng.annotations.Test;

import com.wikia.webdriver.Common.Core.CommonFunctions;
import com.wikia.webdriver.Common.Core.Global;
import com.wikia.webdriver.Common.Properties.Properties;
import com.wikia.webdriver.Common.Templates.TestTemplate;
import com.wikia.webdriver.PageObjects.PageObject.WikiBasePageObject;
import com.wikia.webdriver.PageObjects.PageObject.WikiPage.WikiArticleEditMode;
import com.wikia.webdriver.PageObjects.PageObject.WikiPage.WikiArticlePageObject;

public class ArticleFeaturesCRUDTestsAdmin extends TestTemplate
{
	private String videoURL = "http://www.youtube.com/watch?v=pZB6Dg1RJ_o";
	private String Caption = "QAWebdriverCaption1";
	private String Caption2 = "QAWebdriverCaption2";
	private String pageName;
	
	@Test(groups={"ArticleFeaturesCRUDAdmin_001", "ArticleCRUDAdmin"}) 
//	https://internal.wikia-inc.com/wiki/QA/Core_Features_and_Testing/Manual_Regression_Tests/Image_Serving	
	// Test Case 007  Adding galleries to an article in edit mode
	public void ArticleCRUDAdmin_001_AddingGallery()
	{
		CommonFunctions.logOut(Properties.userName, driver);
		CommonFunctions.logIn(Properties.userNameStaff, Properties.passwordStaff);
		WikiBasePageObject wiki = new WikiBasePageObject(driver, Global.DOMAIN);
		pageName = "QAarticle"+wiki.getTimeStamp();
		wiki.openWikiPage();
		WikiArticleEditMode edit = wiki.createNewArticle(pageName, 1);
		edit.deleteArticleContent();
		edit.clickOnVisualButton();
		edit.clickOnAddObjectButton("Gallery");
		edit.waitForObjectModalAndClickAddAphoto("Gallery");
		edit.galleryCheckImageInputs(4);
		edit.galleryClickOnSelectButton();
		edit.gallerySetPositionGallery("Center");//error!!!
		edit.gallerySetPhotoOrientation(2);
		edit.galleryClickOnFinishButton();
		edit.verifyObjectInEditMode("gallery");
		edit.clickOnPreviewButton();
		edit.verifyTheObjectOnThePreview("gallery");
		WikiArticlePageObject article = edit.clickOnPublishButtonPreview();
		article.verifyTheObjectOnThePage("gallery");
		edit = article.Edit();
		edit.deleteArticleContent();
		article = edit.clickOnPublishButton();
		article.deleteArticle();
		article.openArticle(pageName);
		article.verifyDeletedArticlePage(pageName);
		CommonFunctions.logOut(Properties.userName2, driver);
	}
	
	@Test(groups={"ArticleFeaturesCRUDAdmin_002", "ArticleCRUDAdmin"})
//	https://internal.wikia-inc.com/wiki/QA/Core_Features_and_Testing/Manual_Regression_Tests/Image_Serving	
	// Test Case 008 Adding slideshows to an article in edit mode
	public void ArticleCRUDAdmin_002_AddingSlideshow()
	{
		CommonFunctions.logOut(Properties.userName, driver);
		CommonFunctions.logIn(Properties.userNameStaff, Properties.passwordStaff);
		WikiBasePageObject wiki = new WikiBasePageObject(driver, Global.DOMAIN);
		pageName = "QAarticle"+wiki.getTimeStamp();
		wiki.openWikiPage();
		WikiArticleEditMode edit = wiki.createNewArticle(pageName, 1);
		edit.deleteArticleContent();
		edit.clickOnVisualButton();
		edit.clickOnAddObjectButton("Slideshow");
		edit.waitForObjectModalAndClickAddAphoto("GallerySlideshow");
		edit.galleryCheckImageInputs(4);
		edit.galleryClickOnSelectButton();
		edit.gallerySetPositionSlideshow("Center");
		edit.galleryClickOnFinishButton();
		edit.verifyObjectInEditMode("slideshow");
		edit.clickOnPreviewButton();
		edit.verifyTheObjectOnThePreview("slideshow");
		WikiArticlePageObject article = edit.clickOnPublishButtonInPreviewMode();
		article.verifyTheObjectOnThePage("slideshow");
		edit = article.Edit();
		edit.deleteArticleContent();
		article = edit.clickOnPublishButton();
		article.deleteArticle();
		article.openArticle(pageName);
		article.verifyDeletedArticlePage(pageName);
		CommonFunctions.logOut(Properties.userNameStaff, driver);
	}
	
	@Test(groups={"ArticleFeaturesCRUDAdmin_003", "ArticleCRUDAdmin"})
//	https://internal.wikia-inc.com/wiki/QA/Core_Features_and_Testing/Manual_Regression_Tests/Image_Serving	
	// Test Case 009 Adding sliders to an article in edit mode
	public void ArticleCRUDAdmin_003_AddingSlider()
	{
		CommonFunctions.logOut(Properties.userName, driver);
		CommonFunctions.logIn(Properties.userNameStaff, Properties.passwordStaff);
		WikiBasePageObject wiki = new WikiBasePageObject(driver, Global.DOMAIN);
		pageName = "QAarticle"+wiki.getTimeStamp();
		wiki.openWikiPage();
		WikiArticleEditMode edit = wiki.createNewArticle(pageName, 1);
		edit.deleteArticleContent();
		edit.clickOnVisualButton();
		edit.clickOnAddObjectButton("Slider");
		edit.waitForObjectModalAndClickAddAphoto("GallerySlider");
		edit.galleryCheckImageInputs(4);
		edit.galleryClickOnSelectButton();
		edit.gallerySetSliderPosition(2);
		edit.galleryClickOnFinishButton();
		edit.verifyObjectInEditMode("gallery-slider");
		edit.clickOnPreviewButton();
		edit.verifyTheObjectOnThePreview("slider");
		WikiArticlePageObject article = edit.clickOnPublishButtonInPreviewMode();
		article.verifyTheObjectOnThePage("slider");
		edit = article.Edit();
		edit.deleteArticleContent();
		article = edit.clickOnPublishButton();
		article.deleteArticle();
		article.openArticle(pageName);
		article.verifyDeletedArticlePage(pageName);
		CommonFunctions.logOut(Properties.userName, driver);	
	}
	
	@Test(groups={"ArticleFeaturesCRUDAdmin_004", "ArticleCRUDAdmin"})
//	https://internal.wikia-inc.com/wiki/QA/Core_Features_and_Testing/Manual_Regression_Tests/Image_Serving	
	// Test Case 010 Adding videos to an article in edit mode
	public void ArticleCRUDAdmin_004_AddingVideo()
	{
		CommonFunctions.logOut(Properties.userName, driver);
		CommonFunctions.logIn(Properties.userNameStaff, Properties.passwordStaff);
		WikiBasePageObject wiki = new WikiBasePageObject(driver, Global.DOMAIN);
		pageName = "QAarticle"+wiki.getTimeStamp();
		wiki.openWikiPage();
		WikiArticleEditMode edit = wiki.createNewArticle(pageName, 1);
		edit.deleteArticleContent();
		edit.clickOnVisualButton();
		edit.clickOnAddObjectButton("Video");
		edit.waitForVideoModalAndTypeVideoURL(videoURL);
		edit.clickVideoNextButton();
		edit.waitForVideoDialog();
		edit.typeVideoCaption(Caption);
		edit.clickAddAvideo();
		edit.waitForSuccesDialogAndReturnToEditing();
		edit.verifyVideoInEditMode();
		edit.clickOnPreviewButton();
		edit.verifyTheVideoOnThePreview();
		WikiArticlePageObject article = edit.clickOnPublishButtonInPreviewMode();
		article.verifyTheVideoOnThePage();
		edit = article.Edit();
		edit.deleteArticleContent();
		article = edit.clickOnPublishButton();
		article.deleteArticle();
		article.openArticle(pageName);
		article.verifyDeletedArticlePage(pageName);
		CommonFunctions.logOut(Properties.userName2, driver);
	}	
	
	@Test(groups={"ArticleFeaturesCRUDAdmin_005", "ArticleCRUDAdmin"}) 
//	https://internal.wikia-inc.com/wiki/QA/Core_Features_and_Testing/Manual_Regression_Tests/Image_Serving
	// Test Case 004 Adding images to an article in edit mode
	public void ArticleCRUDAdmin_005_AddingImage()
	{
		CommonFunctions.logOut(Properties.userName, driver);
		CommonFunctions.logIn(Properties.userNameStaff, Properties.passwordStaff);
		WikiBasePageObject wiki = new WikiBasePageObject(driver, Global.DOMAIN);
		pageName = "QAarticle"+wiki.getTimeStamp();
		wiki.openWikiPage();
		WikiArticleEditMode edit = wiki.createNewArticle(pageName, 1);
		edit.deleteArticleContent();
		edit.clickOnVisualButton();
		edit.clickOnAddObjectButton("Image");
		edit.waitForModalAndClickAddThisPhoto();
		edit.typePhotoCaption(Caption);
		edit.clickOnAddPhotoButton2();
		edit.verifyThatThePhotoAppears(Caption);
		edit.clickOnPreviewButton();
		edit.verifyTheImageOnThePreview();
		edit.verifyTheCaptionOnThePreview(Caption);
		WikiArticlePageObject article = edit.clickOnPublishButtonInPreviewMode();
		article.VerifyTheImageOnThePage();
		edit = article.Edit();
		edit.deleteArticleContent();
		article = edit.clickOnPublishButton();
		article.deleteArticle();
		article.openArticle(pageName);
		article.verifyDeletedArticlePage(pageName);
		CommonFunctions.logOut(Properties.userName2, driver);
	}
	
	@Test(groups={"ArticleFeaturesCRUDAdmin_006", "ArticleCRUDAdmin"})
//	https://internal.wikia-inc.com/wiki/QA/Core_Features_and_Testing/Manual_Regression_Tests/Image_Serving
	// Test Case 005 Modifying images in an article in edit mode
	public void ArticleCRUDAdmin_006_ModifyImage()
	{
		CommonFunctions.logOut(Properties.userName, driver);
		CommonFunctions.logIn(Properties.userNameStaff, Properties.passwordStaff);
		WikiBasePageObject wiki = new WikiBasePageObject(driver, Global.DOMAIN);
		pageName = "QAarticle"+wiki.getTimeStamp();
		wiki.openWikiPage();
		WikiArticleEditMode edit = wiki.createNewArticle(pageName, 1);
		edit.deleteArticleContent();
		edit.clickOnVisualButton();
		edit.clickOnAddObjectButton("Image");
		edit.waitForModalAndClickAddThisPhoto();
		edit.typePhotoCaption(Caption);
		edit.clickOnAddPhotoButton2();
		edit.verifyThatThePhotoAppears(Caption);
		edit.clickModifyButtonOfImage(Caption);
		edit.typePhotoCaption(Caption2);
		edit.clickOnAddPhotoButton2();
		edit.verifyThatThePhotoAppears(Caption2);
		edit.clickOnPreviewButton();
		edit.verifyTheImageOnThePreview();
		edit.verifyTheCaptionOnThePreview(Caption2);
		WikiArticlePageObject article = edit.clickOnPublishButtonInPreviewMode();
		article.VerifyTheImageOnThePage();
		article.deleteArticle();
		article.openArticle(pageName);
		article.verifyDeletedArticlePage(pageName);
		CommonFunctions.logOut(Properties.userName2, driver);
	}
	
	@Test(groups={"ArticleFeaturesCRUDAdmin_007", "ArticleCRUDAdmin"})
//	https://internal.wikia-inc.com/wiki/QA/Core_Features_and_Testing/Manual_Regression_Tests/Image_Serving
	// Test Case 005 Modifying images in an article in edit mode
	public void ArticleCRUDAdmin_007_DeleteImage()
	{
		CommonFunctions.logOut(Properties.userName, driver);
		CommonFunctions.logIn(Properties.userNameStaff, Properties.passwordStaff);
		WikiBasePageObject wiki = new WikiBasePageObject(driver, Global.DOMAIN);
		pageName = "QAarticle"+wiki.getTimeStamp();
		wiki.openWikiPage();
		WikiArticleEditMode edit = wiki.createNewArticle(pageName, 1);
		edit.deleteArticleContent();
		edit.clickOnVisualButton();
		edit.clickOnAddObjectButton("Image");
		edit.waitForModalAndClickAddThisPhoto();
		edit.typePhotoCaption(Caption);
		edit.clickOnAddPhotoButton2();
		edit.verifyThatThePhotoAppears(Caption);
		WikiArticlePageObject article = edit.clickOnPublishButton();
		article.VerifyTheImageOnThePage();
		edit = article.Edit();
		edit.clickRemoveButtonOfImage(Caption);
		edit.leftClickOkButton();
		edit.verifyTheImageNotOnTheArticleEditMode();
		article = edit.clickOnPublishButton();
		article.VerifyTheImageNotOnThePage();
		article.deleteArticle();
		article.openArticle(pageName);
		article.verifyDeletedArticlePage(pageName);
		CommonFunctions.logOut(Properties.userName2, driver);
	}	
	
	@Test(groups={"ArticleFeaturesCRUDAdmin_001", "ArticleCRUDAdmin"}) 
//	https://internal.wikia-inc.com/wiki/QA/Core_Features_and_Testing/Manual_Regression_Tests/Image_Serving	
	// Test Case 007  Adding galleries to an article in edit mode
	public void ArticleCRUDAdmin_008_ModifyGallery()
	{
		CommonFunctions.logOut(Properties.userName, driver);
		CommonFunctions.logIn(Properties.userNameStaff, Properties.passwordStaff);
		WikiBasePageObject wiki = new WikiBasePageObject(driver, Global.DOMAIN);
		pageName = "QAarticle"+wiki.getTimeStamp();
		wiki.openWikiPage();
		WikiArticleEditMode edit = wiki.createNewArticle(pageName, 1);
		edit.deleteArticleContent();
		edit.clickOnVisualButton();
		edit.clickOnAddObjectButton("Gallery");
		edit.waitForObjectModalAndClickAddAphoto("Gallery");
		edit.galleryCheckImageInputs(4);
		edit.galleryClickOnSelectButton();
		edit.gallerySetPositionGallery("Center");//error!!!
		edit.gallerySetPhotoOrientation(2);
		edit.galleryClickOnFinishButton();
		edit.verifyObjectInEditMode("gallery");
		edit.clickOnPreviewButton();
		edit.verifyTheObjectOnThePreview("gallery");
		WikiArticlePageObject article = edit.clickOnPublishButtonPreview();
		article.verifyTheObjectOnThePage("gallery");
		article.verifyGalleryPosion("center");
		edit = article.Edit();
		edit.clickModifyButtonGallery();
		edit.waitForObjectModalAndClickAddAphoto("Gallery");
		edit.galleryCheckImageInputs(8);
		edit.galleryClickOnSelectButton();
		edit.gallerySetPositionGallery("Right");
		edit.gallerySetPhotoOrientation(3);
		edit.galleryClickOnFinishButton();
		edit.verifyObjectInEditMode("gallery");
		article = edit.clickOnPublishButton();
		article.verifyGalleryPosion("right");
		article.deleteArticle();
		article.openArticle(pageName);
		article.verifyDeletedArticlePage(pageName);
		CommonFunctions.logOut(Properties.userName2, driver);
	}
	
	@Test(groups={"ArticleFeaturesCRUDAdmin_002", "ArticleCRUDAdmin"})
//	https://internal.wikia-inc.com/wiki/QA/Core_Features_and_Testing/Manual_Regression_Tests/Image_Serving	
	// Test Case 008 Adding slideshows to an article in edit mode
	public void ArticleCRUDAdmin_009_ModifySlideshow()
	{
		CommonFunctions.logOut(Properties.userName, driver);
		CommonFunctions.logIn(Properties.userNameStaff, Properties.passwordStaff);
		WikiBasePageObject wiki = new WikiBasePageObject(driver, Global.DOMAIN);
		pageName = "QAarticle"+wiki.getTimeStamp();
		wiki.openWikiPage();
		WikiArticleEditMode edit = wiki.createNewArticle(pageName, 1);
		edit.deleteArticleContent();
		edit.clickOnVisualButton();
		edit.clickOnAddObjectButton("Slideshow");
		edit.waitForObjectModalAndClickAddAphoto("GallerySlideshow");
		edit.galleryCheckImageInputs(4);
		edit.galleryClickOnSelectButton();
		edit.gallerySetPositionSlideshow("Center");
		edit.galleryClickOnFinishButton();
		edit.verifyObjectInEditMode("slideshow");
		edit.clickOnPreviewButton();
		edit.verifyTheObjectOnThePreview("slideshow");
		WikiArticlePageObject article = edit.clickOnPublishButtonInPreviewMode();
		article.verifyTheObjectOnThePage("slideshow");
		article.verifySlideshowPosition("center");
		edit = article.Edit();
		edit.clickModifyButtonSlideshow();
		edit.waitForObjectModalAndClickAddAphoto("GallerySlideshow");
		edit.galleryCheckImageInputs(8);
		edit.galleryClickOnSelectButton();
		edit.gallerySetPositionSlideshow("Right");
		edit.galleryClickOnFinishButton();
		edit.verifyObjectInEditMode("slideshow");
		article = edit.clickOnPublishButton();
		article.verifyTheObjectOnThePage("slideshow");
		article.verifySlideshowPosition("right");
		article.deleteArticle();
		article.openArticle(pageName);
		article.verifyDeletedArticlePage(pageName);
		CommonFunctions.logOut(Properties.userNameStaff, driver);
	}
}